<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Orvella') }} - Clinic Consultation & Specialist Diagnostics</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Third-party libraries -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --primary: #1E3A8A; /* medical deep blue */
            --secondary: #0ea5e9; /* sky blue */
            --accent: #2563EB; /* modern blue */
        }
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
        }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .glass-dark {
            background: rgba(30, 58, 138, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.4);
            transform: translateY(-1px);
        }
        .modern-shadow {
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* SweetAlert Customization */
        div:where(.swal2-container) h2:where(.swal2-title) {
            font-family: 'Outfit', sans-serif !important;
            font-weight: 700;
        }
        div:where(.swal2-container) div:where(.swal2-html-container) {
            font-family: 'Inter', sans-serif !important;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div id="app">
        <!-- Global Page Loading Indicator (slim top bar) -->
        <div id="global-page-loader" class="fixed top-0 left-0 right-0 z-[9999] pointer-events-none" style="display:none;">
            <div id="global-page-loader-bar" class="h-[3px] bg-gradient-to-r from-blue-500 via-indigo-500 to-blue-600 shadow-lg shadow-blue-500/50 transition-all duration-300" style="width:0%"></div>
        </div>

        @auth
            <!-- Navigation will go here -->
        @endauth

        <main>
            @yield('content')
        </main>
    </div>
    <script>
        // Override native alert with SweetAlert2 globally
        window.nativeAlert = window.alert;
        window.alert = function(message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Notice',
                    text: message,
                    icon: 'warning',
                    confirmButtonColor: '#2563EB',
                    confirmButtonText: 'OK',
                    customClass: {
                        popup: 'rounded-2xl',
                        confirmButton: 'px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl'
                    }
                });
            } else {
                window.nativeAlert(message);
            }
        };

        // Slim top-bar progress loader
        const PageLoader = {
            bar: null,
            wrap: null,
            _timer: null,
            _progress: 0,
            init() {
                this.wrap = document.getElementById('global-page-loader');
                this.bar  = document.getElementById('global-page-loader-bar');
            },
            start() {
                if (!this.bar) this.init();
                this._progress = 10;
                this.wrap.style.display = 'block';
                this.bar.style.width = '10%';
                this.bar.style.opacity = '1';
                clearInterval(this._timer);
                this._timer = setInterval(() => {
                    if (this._progress < 85) {
                        this._progress += Math.random() * 8;
                        this.bar.style.width = Math.min(this._progress, 85) + '%';
                    }
                }, 300);
            },
            finish() {
                if (!this.bar) return;
                clearInterval(this._timer);
                this._progress = 100;
                this.bar.style.width = '100%';
                setTimeout(() => {
                    this.bar.style.opacity = '0';
                    setTimeout(() => { this.wrap.style.display = 'none'; this.bar.style.width = '0%'; this.bar.style.opacity = '1'; }, 300);
                }, 200);
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            PageLoader.init();
            
            // Show slim bar when navigating away (clicking links)
            document.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    const target = this.getAttribute('target');
                    if (href && !href.startsWith('#') && !href.startsWith('javascript') && target !== '_blank' && !e.ctrlKey && !e.metaKey) {
                        PageLoader.start();
                    }
                });
            });
            
            // Show slim bar on form submits - EXCLUDE upload form (it has its own progress bar)
            document.querySelectorAll('form').forEach(form => {
                // Skip the scan upload form
                if (form.action && form.action.includes('upload')) return;
                form.addEventListener('submit', function() {
                    PageLoader.start();
                });
            });
        });
        
        // Finish loader when page is shown (e.g. back button cache)
        window.addEventListener('pageshow', function() {
            PageLoader.finish();
        });
    </script>
</body>
</html>
