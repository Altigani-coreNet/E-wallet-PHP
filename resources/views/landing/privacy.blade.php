@extends('layouts.landing')

@section('title', 'Privacy Policy')
@section('meta_description', 'Privacy policy for Corenet Tech payment and POS solutions. Learn how we protect your data.')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="mb-4">Privacy Policy</h1>
            <p class="text-muted mb-5">Last updated: {{ date('F d, Y') }}</p>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3">1. Introduction</h2>
                    <p>At Corenet Tech, we take your privacy seriously. This policy describes how we collect, use, and protect your personal information.</p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3">2. Information We Collect</h2>
                    <p>We collect the following types of information:</p>
                    <ul>
                        <li>Account information (name, email, phone)</li>
                        <li>Business information</li>
                        <li>Transaction data</li>
                        <li>Device information</li>
                        <li>Usage data</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3">3. How We Use Your Information</h2>
                    <p>We use your information to:</p>
                    <ul>
                        <li>Process payments</li>
                        <li>Provide POS services</li>
                        <li>Improve our services</li>
                        <li>Communicate with you</li>
                        <li>Comply with regulations</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3">4. Data Security</h2>
                    <p>We protect your data through:</p>
                    <ul>
                        <li>Encryption in transit and at rest</li>
                        <li>Regular security audits</li>
                        <li>Access controls</li>
                        <li>Employee training</li>
                        <li>Compliance with industry standards</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3">5. Data Sharing</h2>
                    <p>We may share your information with:</p>
                    <ul>
                        <li>Payment processors</li>
                        <li>Banking partners</li>
                        <li>Service providers</li>
                        <li>Regulatory authorities</li>
                    </ul>
                    <p>We never sell your personal information.</p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3">6. Your Rights</h2>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access your data</li>
                        <li>Correct your data</li>
                        <li>Delete your data</li>
                        <li>Export your data</li>
                        <li>Opt out of communications</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3">7. Cookies</h2>
                    <p>We use cookies to:</p>
                    <ul>
                        <li>Maintain your session</li>
                        <li>Remember your preferences</li>
                        <li>Analyze site usage</li>
                        <li>Improve our services</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3">8. Contact Us</h2>
                    <p>For privacy-related inquiries, contact our Data Protection Officer at:</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> privacy@corenettech.com</li>
                        <li><i class="fas fa-phone me-2"></i> +1 (555) 123-4567</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
