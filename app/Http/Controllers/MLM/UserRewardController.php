<?php

namespace App\Http\Controllers\MLM;

use App\Http\Controllers\Controller;
use App\Models\UserReward;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UserRewardController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $userRewards = UserReward::select(
                'user_rewards.*',
                'mlm_users.first_name',
                'mlm_users.last_name',
                'rewards.name as reward_name',
                'ranks.name as rank_name'
            )
            ->leftJoin('mlm_users', 'user_rewards.mlm_user_id', '=', 'mlm_users.id')
            ->leftJoin('rewards', 'user_rewards.reward_id', '=', 'rewards.id')
            ->leftJoin('ranks', 'user_rewards.rank_id', '=', 'ranks.id');

            return DataTables::of($userRewards)
                ->addIndexColumn()
                ->addColumn('user_name', fn($row) => $row->first_name . ' ' . $row->last_name)
                ->addColumn('reward_name', fn($row) => $row->reward_name)
                ->addColumn('rank_name', fn($row) => $row->rank_name)
                ->addColumn('achieved_at', fn($row) => $row->achieved_at ? $row->achieved_at->format('d-m-Y') : '-')
                ->addColumn('claimed_at', fn($row) => $row->claimed_at ? $row->claimed_at->format('d-m-Y') : '-')
                ->addColumn('status', function ($row) {
                    $map = [
                        'pending'  => ['label' => 'Pending',  'class' => 'bg-warning text-dark'],
                        'claimed'  => ['label' => 'Claimed',  'class' => 'bg-success'],
                        'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-danger'],
                    ];
                    $item = $map[$row->status] ?? ['label' => ucfirst($row->status), 'class' => 'bg-secondary'];
                    return '<span class="badge ' . $item['class'] . '">' . $item['label'] . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    return '<button class="btn btn-sm btn-primary update-status-button"
                                data-id="' . $row->id . '"
                                data-status="' . e($row->status) . '">
                                <i class="fas fa-edit"></i> Update
                            </button>';
                })
                ->rawColumns(['status', 'actions'])
                ->make(true);
        }

        return view('admin.pages.user-rewards.index');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,claimed,cancelled',
            'notes'  => 'nullable|string',
        ]);

        $userReward = UserReward::findOrFail($id);
        $data = ['status' => $request->status];

        if ($request->status === 'claimed') {
            $data['claimed_at'] = now();
        }

        if ($request->has('notes')) {
            $data['notes'] = $request->notes;
        }

        $userReward->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Reward status updated successfully.',
        ]);
    }
}
