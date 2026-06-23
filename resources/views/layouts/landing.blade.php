<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Corenet Tech</title>
    <meta name="description" content="@yield('meta_description', 'Corenet Tech offers comprehensive payment processing, POS management, and banking solutions for modern businesses.')">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #20AA3E 0%, #03A588 100%);
        }
        .nav-link {
            color: #333;
            font-weight: 500;
        }
        .nav-link:hover {
            color: #20AA3E;
        }
        .btn-primary {
            background-color: #20AA3E;
            border-color: #20AA3E;
        }
        .btn-primary:hover {
            background-color: #178F33;
            border-color: #178F33;
        }
        .btn-outline-primary {
            color: #20AA3E;
            border-color: #20AA3E;
        }
        .btn-outline-primary:hover {
            background-color: #20AA3E;
            border-color: #20AA3E;
        }
        .footer-link {
            color: #6c757d;
            text-decoration: none;
            transition: color 0.3s;
        }
        .footer-link:hover {
            color: #20AA3E;
        }
    </style>
    @yield('additional_css')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                <img src="{{ asset('logo.png') }}" alt="Corenet Tech Logo" height="40">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/#features') }}">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/#solutions') }}">Solutions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/#pricing') }}">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/#contact') }}">Contact</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="{{ route('merchant.login') }}" class="btn btn-outline-primary me-2">Sign In</a>
                    <a href="{{ route('merchant.register') }}" class="btn btn-primary">Get Started</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main style="margin-top: 76px;">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <img src="{{ asset('logo_light.jpg') }}" alt="Corenet Tech Logo" height="30" class="mb-3">
                    <p class="text-muted">Complete payment and POS solution for modern businesses.</p>
                </div>
                <div class="col-md-2">
                    <h5>Product</h5>
                    <ul class="list-unstyled">
                        <li><a href="{{ url('/#features') }}" class="footer-link">Features</a></li>
                        <li><a href="{{ url('/#pricing') }}" class="footer-link">Pricing</a></li>
                        <li><a href="{{ url('/#security') }}" class="footer-link">Security</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h5>Company</h5>
                    <ul class="list-unstyled">
                        <li><a href="{{ url('/about') }}" class="footer-link">About</a></li>
                        <li><a href="{{ url('/contact') }}" class="footer-link">Contact</a></li>
                        <li><a href="{{ url('/careers') }}" class="footer-link">Careers</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Legal</h5>
                    <ul class="list-unstyled">
                        <li><a href="{{ url('/terms') }}" class="footer-link">Terms & Conditions</a></li>
                        <li><a href="{{ url('/privacy') }}" class="footer-link">Privacy Policy</a></li>
                        <li><a href="{{ url('/compliance') }}" class="footer-link">Compliance</a></li>
                    </ul>
                </div>
            </div>
            <hr class="mt-4 mb-3">
            <div class="text-center text-muted">
                <small>&copy; {{ date('Y') }} Corenet Tech. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('additional_js')
</body>
</html>
