<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MlmUser;
use App\Models\MLMTree;
use App\Http\Resources\MlmUserResource;
use App\Services\MailNotificationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MLMApiController extends Controller
{
    /**
     * ✅ 1. Direct Referrals (Level 1)
     */
    public function getReferrals(Request $request)
    {
        $request->validate([
            'user_id'  => 'required',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = MlmUser::findOrFail($request->user_id);

        $baseQuery = MlmUser::query()
            ->with(['detail'])
            ->where('sponsor_id', $user->id)
            ->where('is_deleted', false);

        $referrals = (clone $baseQuery)
            ->with(['payoutBalance', 'currentRank.rank'])
            ->latest()
            ->paginate($request->input('per_page', 12));

        $stats = [
            'total' => (clone $baseQuery)->count(),

            'active' => (clone $baseQuery)
                ->where('is_active', true)
                ->count(),

            'total_cc' => (clone $baseQuery)
                ->withSum('payoutBalance', 'cc_balance')
                ->get()
                ->sum('payout_balance_sum_cc_balance'),
        ];

        return response()->json([
            'success'   => true,
            'message'   => 'Referrals fetched successfully.',
            'referrals' => MlmUserResource::collection($referrals),
            'stats'     => $stats,
        ]);
    }

    /**
     * ✅ 2. Referral Profile (Modal Data)
     */
    public function getReferralProfile($userId)
    {
        try {
            $user = MlmUser::with(['sponsor:id,user_name,first_name,last_name', 'detail', 'payoutBalance'])
                ->findOrFail($userId);

            return response()->json([
                'success' => true,
                'user' => new MlmUserResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
    }

    /**
     * ✅ 3. Referral Downline
     */
      public function getReferralDownline(Request $request)
    {
        $request->validate(['user_id' => 'required']);
        $user = MlmUser::findOrFail($request->user_id);
        
        $query = MlmUser::with(['sponsor', 'payoutBalance'])
            ->where('sponsor_id', $user->id)
            ->where('is_deleted', false);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('user_name', 'LIKE', "%{$search}%")
                  ->orWhere('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);
        
        $query->orderBy($request->get('sort', 'created_at'), $request->get('order', 'desc'));
        $downlines = $query->paginate($request->get('per_page', 20));
        
        $stats = [
            'total' => MlmUser::where('sponsor_id', $user->id)->where('is_deleted', false)->count(),
            'active' => MlmUser::where('sponsor_id', $user->id)->where('is_active', true)->where('is_deleted', false)->count(),
            'inactive' => MlmUser::where('sponsor_id', $user->id)->where('is_active', false)->where('is_deleted', false)->count(),
            'total_earned' => MlmUser::where('sponsor_id', $user->id)
                ->where('is_deleted', false)
                ->withSum('payoutBalance', 'total_earned')
                ->get()
                ->sum('payout_balance_sum_total_earned'),
        ];
        
        return response()->json(['success' => true, 'downlines' => MlmUserResource::collection($downlines), 'stats' => $stats]);
    }

    /**
     * ✅ 3. Holding Tank Users
     */
    public function getHoldingTank(Request $request)
    {
        $query = MLMTree::with(['mlmUser.sponsor'])
            ->whereHas('mlmUser', fn($q) => $q->where('is_verified', true)->where('is_active', true))
            ->whereNull('parent_id')->where('position', 'none')
            ->whereHas('mlmUser', fn($q) => $q->where('user_name', '!=', 'Founder01'));

        // If sponsor_id is provided, filter to only show pending users under that sponsor
        // Accept comma-separated IDs or an array to support multiple sponsors
        if ($request->filled('sponsor_id')) {
            $sponsorIds = $request->sponsor_id;
            if (is_string($sponsorIds)) {
                $sponsorIds = explode(',', $sponsorIds);
            }
            $sponsorIds = array_map('trim', (array) $sponsorIds);
            $sponsorIds = array_filter($sponsorIds, fn($id) => is_numeric($id));
            if (!empty($sponsorIds)) {
                $query->whereHas('mlmUser', fn($q) => $q->whereIn('sponsor_id', $sponsorIds));
            }
        }

        $holdingUsers = $query->latest()->paginate($request->get('per_page', 15));

        // Only show parents with at least one free position (left or right vacant)
        $parents = MlmUser::where('is_active', true)->where('is_deleted', false)
            ->whereHas('tree', function ($q) {
                $q->where(function ($placed) {
                    // Must be placed in tree (has parent_id) OR be the root user
                    $placed->whereNotNull('parent_id')->orWhere('mlm_user_id', 1);
                })
                ->where(function ($free) {
                    // At least one position must be vacant
                    $free->whereDoesntHave('children', fn($c) => $c->where('position', 'left'))
                         ->orWhereDoesntHave('children', fn($c) => $c->where('position', 'right'));
                });
            })
            ->orderBy('user_name')->get(['id', 'user_name', 'first_name', 'last_name'])
            ->map(fn($p) => ['id' => $p->id, 'user_name' => $p->user_name, 'first_name' => $p->first_name, 'last_name' => $p->last_name]);

        return response()->json(['success' => true, 'holding_users' => $holdingUsers, 'parents' => $parents]);
    }

    /**
     * ✅ 4. Place User in Binary Tree (POST)
     */
    public function placeUser(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'parent_id' => 'required',
            'position' => 'required|in:left,right',
            'sponsor_id' => 'nullable',
        ]);

        $validated['user_id'] = MlmUser::where('id', $validated['user_id'])->value('id');
        $validated['parent_id'] = MlmUser::where('id', $validated['parent_id'])->value('id');

        if ($validated['user_id'] == $validated['parent_id']) {
            return response()->json(['success' => false, 'message' => 'Cannot place user under themselves.'], 422);
        }

        // If sponsor_id is provided, verify the user being placed belongs to that sponsor
        // Also allow if the authenticated user is a member of that sponsor's team
        if (!empty($validated['sponsor_id'])) {
            $sponsorId = $validated['sponsor_id'];
            $userToPlace = MlmUser::find($validated['user_id']);
            if (!$userToPlace || $userToPlace->sponsor_id != $sponsorId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only place users who are under your sponsorship.'
                ], 403);
            }
            // Allow if the requester is the sponsor OR a member of the sponsor's team
            $requester = auth()->user();
            if ($requester && $requester->id != $sponsorId) {
                $isTeamMember = MlmUser::where('id', $requester->id)
                    ->where('sponsor_id', $sponsorId)
                    ->exists();
                if (!$isTeamMember) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not authorized to place users under this sponsor.'
                    ], 403);
                }
            }
        }

        DB::beginTransaction();
        try {
            $userTree = MLMTree::where('mlm_user_id', $validated['user_id'])
                ->where(function($q) { $q->whereNull('parent_id')->orWhere('position', 'none'); })
                ->with('mlmUser')->firstOrFail();
            
            $user = $userTree->mlmUser;
            if (!$user->is_verified || !$user->is_active) {
                throw new \Exception('User must be verified and active.');
            }

            $parentTree = MLMTree::where('mlm_user_id', $validated['parent_id'])->firstOrFail();
            
            if (MLMTree::where('parent_id', $parentTree->id)->where('position', $validated['position'])->exists()) {
                throw new \Exception("Position '{$validated['position']}' already occupied.");
            }

            $userTree->update([
                'parent_id' => $parentTree->id,
                'position' => $validated['position'],
                'level' => $parentTree->level + 1,
            ]);

            if (class_exists(\App\Services\MLMClosureService::class)) {
                app(\App\Services\MLMClosureService::class)->syncClosures($userTree, $validated['parent_id']);
            }
            
            $user->update(['position_in_sponsor_leg' => $validated['position']]);

            // In-app notification for binary position
            try {
                app(NotificationService::class)->create($user->id, 'registration', 'Binary Position Assigned',
                    "You have been placed in the {$validated['position']} leg.");
            } catch (\Throwable $e) {
                Log::warning('Binary position notification failed: ' . $e->getMessage());
            }

            // Binary position email
            try {
                $parentName = $parentTree->mlmUser?->user_name ?? null;
                app(MailNotificationService::class)->sendBinaryPosition($user, $validated['position'], $parentName);
            } catch (\Throwable $e) {
                Log::warning('Binary position email failed: ' . $e->getMessage());
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => "User placed in {$validated['position']} leg!"]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Place User API Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

   
    /**
     * ✅ 6. Team Genealogy (Visual Binary Tree Data)
     */
     public function getTeamGenealogy(Request $request)
    {
        // ✅ FIX: Get user from request parameter instead of Auth
        $currentUser = MlmUser::find($request->user_id);
        if (!$currentUser) return response()->json(['success' => false, 'message' => 'Invalid user_id provided'], 400);
        
        $rootTree = MLMTree::where('mlm_user_id', $currentUser->id)
            ->with(['leftChild.mlmUser', 'rightChild.mlmUser'])
            ->first();
        
        $treeData = $this->buildTreeStructure($rootTree);
        
        return response()->json([
            'success' => true,
            'tree_data' => $treeData,
            'root_user' => $currentUser
        ]);
    }

    /**
     * ✅ 7. Team Downline (Full Binary Table)
     */
    public function getTeamDownline(Request $request)
    {
        // ✅ FIX: Get user from request parameter instead of Auth
        $currentUser = MlmUser::find($request->user_id);
        if (!$currentUser) return response()->json(['success' => false, 'message' => 'Invalid user_id provided'], 400);
        
        $query = MLMTree::with(['mlmUser.sponsor', 'mlmUser.payoutBalance', 'parent'])
            ->whereHas('mlmUser', function($q) use ($currentUser) {
                $q->where('is_deleted', false);
                if ($currentUser->user_name !== 'Founder01') {
                    $q->whereIn('id', $this->getAllDownlineIds($currentUser->id));
                }
            })
            ->orderBy('level', 'asc')->orderBy('created_at', 'asc');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('mlmUser', fn($q) => $q->where('user_name', 'LIKE', "%{$search}%")
              ->orWhere('first_name', 'LIKE', "%{$search}%")->orWhere('last_name', 'LIKE', "%{$search}%"));
        }
        if ($request->filled('level')) $query->where('level', $request->level);
        if ($request->filled('position')) $query->where('position', $request->position);
        if ($request->filled('status')) {
            $query->whereHas('mlmUser', fn($q) => $q->where('is_active', $request->status === 'active'));
        }
        
        $teamMembers = $query->paginate($request->get('per_page', 50));
        $downlineIds = $this->getAllDownlineIds($currentUser->id);
        
        $stats = [
            'total' => MLMTree::whereIn('mlm_user_id', $downlineIds)->count(),
            'level_1' => MLMTree::whereIn('mlm_user_id', $downlineIds)->where('level', 1)->count(),
            'level_2' => MLMTree::whereIn('mlm_user_id', $downlineIds)->where('level', 2)->count(),
            'left_leg' => MLMTree::whereIn('mlm_user_id', $downlineIds)->where('position', 'left')->count(),
            'right_leg' => MLMTree::whereIn('mlm_user_id', $downlineIds)->where('position', 'right')->count(),
        ];
        
        return response()->json(['success' => true, 'team_members' => $teamMembers, 'stats' => $stats]);
    }

    /**
     * ✅ 8. Detailed User Profile (Left/Right Team Stats)
     */
    public function getUserProfile($userId)
    {
        try {
            $user = MlmUser::with(['sponsor', 'payoutBalance', 'tree', 'currentRank.rank'])->findOrFail($userId);
            $tree = $user->tree;
            $stats = $this->calculateUserStats($user, $tree);
            $stats['rank'] = $user->currentRank?->rank?->name ?? 'Fresh';
            
            return response()->json([
                'success' => true,
                'user' => $user->only(['id', 'user_name', 'first_name', 'last_name', 'email', 'is_active']),
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Genealogy Profile API Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * ✅ 9. Specific User Downline Tree
     */
    public function getUserDownline($userId)
    {
        $selectedUser = MlmUser::findOrFail($userId);
        $rootTree = MLMTree::where('mlm_user_id', $selectedUser->id)
            ->with(['leftChild.mlmUser', 'rightChild.mlmUser'])
            ->first();
        
        $treeData = $this->buildTreeStructure($rootTree);
        
        return response()->json(['success' => true, 'tree_data' => $treeData, 'selected_user' => $selectedUser]);
    }


    // ==========================================
    // 🔒 PRIVATE HELPER METHODS (Tree Logic)
    // ==========================================

    private function buildTreeStructure($treeNode, $isViewRoot = true)
    {
        if (!$treeNode) return null;
        $user = $treeNode->mlmUser;
        
        $leftChild = $treeNode->leftChild ? 
            MLMTree::where('id', $treeNode->leftChild->id)
                ->with(['leftChild.mlmUser', 'rightChild.mlmUser', 
                        'leftChild.leftChild.mlmUser', 'leftChild.rightChild.mlmUser',
                        'rightChild.leftChild.mlmUser', 'rightChild.rightChild.mlmUser'])
                ->first() : null;
                
        $rightChild = $treeNode->rightChild ?
            MLMTree::where('id', $treeNode->rightChild->id)
                ->with(['leftChild.mlmUser', 'rightChild.mlmUser',
                        'leftChild.leftChild.mlmUser', 'leftChild.rightChild.mlmUser',
                        'rightChild.leftChild.mlmUser', 'rightChild.rightChild.mlmUser'])
                ->first() : null;
        
        return [
            'id' => $treeNode->id, 'user_id' => $user->id, 'user_name' => $user->user_name,
            'first_name' => $user->first_name, 'last_name' => $user->last_name, 'email' => $user->email,
            'position' => $treeNode->position, 'level' => $treeNode->level, 'is_active' => $user->is_active,
            'is_root' => $isViewRoot, 'cc_balance' => $user->payoutBalance?->cc_balance ?? 0,
            'left' => $leftChild ? $this->buildTreeStructure($leftChild, false) : null,
            'right' => $rightChild ? $this->buildTreeStructure($rightChild, false) : null,
        ];
    }

    private function calculateUserStats($user, $tree)
    {
        $leftTeamBv = $rightTeamBv = $activeLeftTeam = $activeRightTeam = $totalLeftTeam = $totalRightTeam = 0;
        if ($tree) {
            $leftChild = MLMTree::where('parent_id', $tree->id)->where('position', 'left')->with('mlmUser.payoutBalance')->first();
            $rightChild = MLMTree::where('parent_id', $tree->id)->where('position', 'right')->with('mlmUser.payoutBalance')->first();
            
            $leftTeamBv = $leftChild?->mlmUser?->payoutBalance?->cc_balance ?? 0;
            $rightTeamBv = $rightChild?->mlmUser?->payoutBalance?->cc_balance ?? 0;
            $activeLeftTeam = $leftChild && $leftChild->mlmUser->is_active ? 1 : 0;
            $activeRightTeam = $rightChild && $rightChild->mlmUser->is_active ? 1 : 0;
            $totalLeftTeam = $this->countDownline($leftChild?->mlm_user_id);
            $totalRightTeam = $this->countDownline($rightChild?->mlm_user_id);
        }
        
        return [
            'sponsor_id' => $user->sponsor?->user_name ?? 'Direct Seller',
            'joined_date' => $user->created_at?->format('d-m-Y') ?? 'N/A',
            'level' => $tree?->level ?? 0,
            'current_right_cc' => $rightTeamBv, 'current_left_cc' => $leftTeamBv,
            'active_right_team' => $activeRightTeam, 'active_left_team' => $activeLeftTeam,
            'total_right_team' => $totalRightTeam, 'total_left_team' => $totalLeftTeam,
            'personal_bv' => $user->payoutBalance?->cc_balance ?? 0,
            'package' => $user->package_name ?? '--',
        ];
    }

    private function getAllDownlineIds($userId, $maxLevel = 100) {
        $ids = [$userId];
        $this->collectDownlineIds($userId, $ids, 0, $maxLevel);
        return array_unique($ids);
    }
    
    private function collectDownlineIds($userId, &$ids, $level, $maxLevel) {
        if ($level >= $maxLevel) return;
        $tree = MLMTree::where('mlm_user_id', $userId)->first();
        if (!$tree) return;
        $children = MLMTree::where('parent_id', $tree->id)->get();
        foreach ($children as $child) {
            $ids[] = $child->mlm_user_id;
            $this->collectDownlineIds($child->mlm_user_id, $ids, $level + 1, $maxLevel);
        }
    }

    private function countDownline($userId, $maxLevel = 100) {
        if (!$userId) return 0;
        $count = 0;
        $this->collectDownlineCount($userId, $count, 0, $maxLevel);
        return $count;
    }

    private function collectDownlineCount($userId, &$count, $level, $maxLevel) {
        if ($level >= $maxLevel) return;
        $tree = MLMTree::where('mlm_user_id', $userId)->first();
        if (!$tree) return;
        $children = MLMTree::where('parent_id', $tree->id)->get();
        foreach ($children as $child) {
            $count++;
            $this->collectDownlineCount($child->mlm_user_id, $count, $level + 1, $maxLevel);
        }
    }
}