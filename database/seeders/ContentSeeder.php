<?php

namespace Database\Seeders;

use App\Models\PrivacyPolicy;
use App\Models\TermsCondition;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        PrivacyPolicy::create([
            'sub_title' => 'Privacy Policy',
            'main_title' => 'Your Privacy Matters',
            'description' => '<h4>Information We Collect</h4><p>We collect personal information such as your name, email address, phone number, and payment details when you register and use our platform.</p><h4>How We Use Your Information</h4><p>Your information is used to process transactions, provide customer support, improve our services, and send relevant updates.</p><h4>Data Protection</h4><p>We implement industry-standard security measures to protect your personal data from unauthorized access, alteration, or disclosure.</p><h4>Cookies</h4><p>Our platform uses cookies to enhance your browsing experience and analyze site traffic.</p><h4>Third-Party Sharing</h4><p>We do not sell or share your personal information with third parties except as required by law or to facilitate payments.</p><h4>Contact</h4><p>If you have any questions about this privacy policy, please contact our support team.</p>',
            'is_active' => 1,
        ]);

        TermsCondition::create([
            'sub_title' => 'Terms & Conditions',
            'main_title' => 'Platform Terms of Use',
            'description' => '<h4>Acceptance of Terms</h4><p>By registering and using this platform, you agree to be bound by these terms and conditions.</p><h4>Account Registration</h4><p>You must provide accurate and complete information during registration. You are responsible for maintaining the confidentiality of your account credentials.</p><h4>Referral Program</h4><p>Our referral program rewards you for introducing new members. All referrals must comply with our compliance policies.</p><h4>Commissions & Payouts</h4><p>Commissions are calculated based on the active referral structure. Payouts are processed subject to minimum withdrawal limits and verification.</p><h4>Prohibited Activities</h4><p>You may not engage in spamming, fraudulent activities, or any actions that violate applicable laws or harm the platform\'s reputation.</p><h4>Termination</h4><p>We reserve the right to suspend or terminate accounts that violate these terms.</p><h4>Modifications</h4><p>We may update these terms at any time. Continued use of the platform constitutes acceptance of the revised terms.</p>',
            'is_active' => 1,
        ]);
    }
}
