<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <title>{{ config('app.name', 'TechForge') }} | Cart & Returns</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#ff6b00', hover: '#e56000', glow: 'rgba(255, 107, 0, 0.5)' },
                        dark: { bg: '#050505', surface: '#121212' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        };
    </script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #050505;
            color: #ffffff;
            overflow-x: hidden;
        }

        /* Ambient Radial Light Blurs */
        .ambient-light-1 {
            position: fixed;
            top: -20%;
            left: -20%;
            width: 70vw;
            height: 70vw;
            background: radial-gradient(circle, rgba(255, 107, 0, 0.35) 0%, rgba(255, 107, 0, 0) 65%);
            z-index: -1;
            pointer-events: none;
            animation: floatPulse1 20s ease-in-out infinite;
        }

        .ambient-light-2 {
            position: fixed;
            top: 35%;
            right: -20%;
            width: 80vw;
            height: 80vw;
            background: radial-gradient(circle, rgba(153, 0, 0, 0.4) 0%, rgba(153, 0, 0, 0) 65%);
            z-index: -1;
            pointer-events: none;
            animation: floatPulse2 25s ease-in-out infinite;
        }

        @keyframes floatPulse1 {
            0% {
                opacity: 0.3;
                transform: translate(0, 0) scale(0.8);
            }
            33% {
                opacity: 0.8;
                transform: translate(25vw, 15vh) scale(1.2);
            }
            66% {
                opacity: 0.4;
                transform: translate(-10vw, 30vh) scale(0.9);
            }
            100% {
                opacity: 0.3;
                transform: translate(0, 0) scale(0.8);
            }
        }

        @keyframes floatPulse2 {
            0% {
                opacity: 0.8;
                transform: translate(0, 0) scale(1.1);
            }
            33% {
                opacity: 0.3;
                transform: translate(-25vw, -15vh) scale(0.8);
            }
            66% {
                opacity: 0.7;
                transform: translate(15vw, -25vh) scale(1.3);
            }
            100% {
                opacity: 0.8;
                transform: translate(0, 0) scale(1.1);
            }
        }

        /* Orange Gradient Text */
        .text-gradient {
            background: linear-gradient(to right, #ffffff, #ffaa66);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #050505;
        }
        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #ff6b00;
        }

        /* Preloader Animations */
        @keyframes spinFastOnce {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(720deg); }
        }
        .animate-spin-fast {
            animation: spinFastOnce 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }
        @keyframes slideTextOut {
            0% { max-width: 0; opacity: 0; padding-left: 0; }
            100% { max-width: 400px; opacity: 1; padding-left: 1.5rem; }
        }
        .animate-slide-text {
            animation: slideTextOut 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            animation-delay: 0.8s;
            overflow: hidden;
            white-space: nowrap;
            opacity: 0;
            max-width: 0;
        }
    </style>

    @vite('Modules/E-Commerce/Techforge/resources/css/liquidglass.css')
</head>
<body class="relative antialiased selection:bg-primary selection:text-white">

    <!-- Preloader -->
    <div id="preloader" class="fixed inset-0 bg-[#050505] z-[100] flex items-center justify-center transition-opacity duration-1000 ease-in-out">
        <script>
            if (!sessionStorage.getItem('techforge_visited')) {
                document.write(`
                    <div class="relative flex items-center justify-center">
                        <div class="absolute inset-0 bg-primary/20 blur-xl rounded-full animate-pulse"></div>
                        <div class="flex items-center relative z-10">
                            <img src="{{ Vite::asset('Modules/E-Commerce/Techforge/resources/img/Techforge_Logo.png') }}" alt="TechForge Logo" class="h-20 w-auto object-contain animate-spin-fast drop-shadow-[0_0_25px_rgba(255,107,0,0.6)]">
                            <span class="text-4xl md:text-5xl font-black text-white tracking-widest animate-slide-text">TECHFORGE</span>
                        </div>
                    </div>
                `);
            } else {
                document.write(`
                    <div class="w-16 h-16 border-4 border-white/10 border-t-primary rounded-full animate-spin shadow-[0_0_20px_rgba(255,107,0,0.3)]"></div>
                `);
            }
        </script>
    </div>

    <!-- Background Ambient Effects -->
    <div class="ambient-light-1"></div>
    <div class="ambient-light-2"></div>



    <x-navbar />

    <!-- Account Section -->
    <main class="relative pt-40 pb-20 lg:pt-48 lg:pb-28 overflow-hidden z-10 min-h-screen">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-14">
            
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
                
                <!-- Sidebar -->
                <div class="lg:col-span-1 flex flex-col gap-6" id="account-sidebar">
                    <div class="flex flex-col gap-4">
                        
                        <!-- Account Details Category -->
                        <div class="flex flex-col">
                            <a href="#profile" data-target="pane-profile" data-dropdown="dropdown-account-details" class="sidebar-link account-details-link flex items-center gap-3 text-gray-400 hover:text-white transition-colors font-bold text-base w-full text-left">
                                <i class="ph ph-user text-xl category-icon text-primary"></i>
                                Account Details
                            </a>
                            <!-- Subcategories -->
                            <div id="dropdown-account-details" class="category-dropdown flex flex-col ml-8 gap-3 border-l border-white/10 pl-4 py-1 mt-2 overflow-hidden transition-all duration-300" style="max-height: 500px;">
                                <a href="#profile" data-target="pane-profile" class="sidebar-link active text-primary font-bold text-sm hover:text-primary transition-colors">Profile</a>
                                <a href="#bank-cards" data-target="pane-bank-cards" class="sidebar-link text-gray-400 hover:text-white transition-colors text-sm">Bank & Cards</a>
                                <a href="#addresses" data-target="pane-addresses" class="sidebar-link text-gray-400 hover:text-white transition-colors text-sm">Addresses</a>
                                <a href="#password" data-target="pane-password" class="sidebar-link text-gray-400 hover:text-white transition-colors text-sm">Change Password</a>
                            </div>
                        </div>

                        <!-- Other Categories -->
                        <a href="#purchases" data-target="pane-purchases" class="sidebar-link main-category-link flex items-center gap-3 text-gray-400 hover:text-white transition-colors font-bold text-base">
                            <i class="ph ph-receipt text-xl category-icon"></i>
                            Purchases
                        </a>
                        <a href="#vouchers" data-target="pane-vouchers" class="sidebar-link main-category-link flex items-center gap-3 text-gray-400 hover:text-white transition-colors font-bold text-base">
                            <i class="ph ph-ticket text-xl category-icon"></i>
                            Vouchers
                        </a>
                        <a href="#forge-points" data-target="pane-forge-points" class="sidebar-link main-category-link flex items-center gap-3 text-gray-400 hover:text-white transition-colors font-bold text-base">
                            <i class="ph ph-coins text-xl category-icon"></i>
                            Forge Points
                        </a>
                    </div>
                </div>

                <!-- Content -->
                <div class="lg:col-span-3 liquid-glass rounded-3xl p-6 sm:p-10 border border-white/10 shadow-2xl relative overflow-hidden min-h-[500px] transition-all duration-500 ease-in-out">
                    <!-- Glassmorphism subtle glow -->
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-primary/20 blur-3xl rounded-full pointer-events-none"></div>

                    <!-- PANE: PROFILE -->
                    <div id="pane-profile" class="content-pane block">

                    <div class="border-b border-white/10 pb-4 mb-8 relative z-10">
                        <h2 class="text-2xl font-black text-white">Profile</h2>
                        <p class="text-sm text-gray-400 mt-1">Manage your account</p>
                    </div>

                    <form action="{{ route('ecommerce.account.profile.update') }}" method="POST" class="flex flex-col-reverse md:flex-row gap-12 relative z-10">
                        @csrf
                        <!-- Form (Left side) -->
                        <div class="flex-1 space-y-6">
                            
                            <!-- Username -->
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6">
                                <label class="w-32 text-sm text-gray-400 font-medium shrink-0">Username</label>
                                <div class="flex-1">
                                    <input type="text" value="{{ Auth::guard('ecommerce')->check() ? Auth::guard('ecommerce')->user()->name : 'user123' }}" disabled class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-gray-500 cursor-not-allowed focus:outline-none shadow-inner">
                                </div>
                            </div>

                            <!-- Name -->
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6">
                                <label class="w-32 text-sm text-gray-400 font-medium shrink-0">Name</label>
                                <div class="flex-1">
                                    <input type="text" name="name" value="{{ Auth::guard('ecommerce')->user()->name ?? '' }}" class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-2.5 text-sm text-white transition-all outline-none placeholder-gray-600">
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6">
                                <label class="w-32 text-sm text-gray-400 font-medium shrink-0">Email</label>
                                <div class="flex-1">
                                    <input type="email" name="email" value="{{ Auth::guard('ecommerce')->user()->email ?? '' }}" class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-2.5 text-sm text-white transition-all outline-none placeholder-gray-600">
                                </div>
                            </div>

                            <!-- Phone Number -->
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6">
                                <label class="w-32 text-sm text-gray-400 font-medium shrink-0">Phone Number</label>
                                <div class="flex-1">
                                    <input type="tel" name="phone" value="{{ Auth::guard('ecommerce')->user()->phone ?? '' }}" placeholder="Enter your phone number" class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-2.5 text-sm text-white transition-all outline-none placeholder-gray-600">
                                </div>
                            </div>

                            <!-- Gender -->
                            <div class="flex flex-col sm:flex-row gap-2 sm:gap-6">
                                <label class="w-32 text-sm text-gray-400 font-medium shrink-0 pt-2">Gender</label>
                                <div class="flex-1 flex items-center gap-8 pt-2">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative flex items-center justify-center">
                                            <input type="radio" name="gender" value="male" class="peer sr-only" {{ (Auth::guard('ecommerce')->user()->gender ?? '') == 'male' ? 'checked' : '' }}>
                                            <div class="w-5 h-5 rounded-full border-2 border-gray-600 peer-checked:border-primary transition-colors"></div>
                                            <div class="w-2.5 h-2.5 bg-primary rounded-full absolute scale-0 peer-checked:scale-100 transition-transform shadow-[0_0_8px_rgba(255,107,0,0.8)]"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-400 group-hover:text-white transition-colors">Male</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative flex items-center justify-center">
                                            <input type="radio" name="gender" value="female" class="peer sr-only" {{ (Auth::guard('ecommerce')->user()->gender ?? '') == 'female' ? 'checked' : '' }}>
                                            <div class="w-5 h-5 rounded-full border-2 border-gray-600 peer-checked:border-primary transition-colors"></div>
                                            <div class="w-2.5 h-2.5 bg-primary rounded-full absolute scale-0 peer-checked:scale-100 transition-transform shadow-[0_0_8px_rgba(255,107,0,0.8)]"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-400 group-hover:text-white transition-colors">Female</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative flex items-center justify-center">
                                            <input type="radio" name="gender" value="other" class="peer sr-only" {{ (Auth::guard('ecommerce')->user()->gender ?? '') == 'other' ? 'checked' : '' }}>
                                            <div class="w-5 h-5 rounded-full border-2 border-gray-600 peer-checked:border-primary transition-colors"></div>
                                            <div class="w-2.5 h-2.5 bg-primary rounded-full absolute scale-0 peer-checked:scale-100 transition-transform shadow-[0_0_8px_rgba(255,107,0,0.8)]"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-400 group-hover:text-white transition-colors">Other</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Date of Birth -->
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6">
                                <label class="w-32 text-sm text-gray-400 font-medium shrink-0">Date of Birth</label>
                                <div class="flex-1 grid grid-cols-3 gap-3">
                                    <div class="relative">
                                        <select name="dob_day" class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl pl-4 pr-10 py-2.5 text-sm text-white transition-all outline-none appearance-none cursor-pointer">
                                            <option value="" disabled selected>DD</option>
                                            @for($i=1; $i<=31; $i++)
                                                <option value="{{ $i }}" {{ (isset(Auth::guard('ecommerce')->user()->dob) && \Carbon\Carbon::parse(Auth::guard('ecommerce')->user()->dob)->day == $i) ? 'selected' : '' }}>{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                            @endfor
                                        </select>
                                        <i class="ph ph-caret-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none"></i>
                                    </div>
                                    <div class="relative">
                                        <select name="dob_month" class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl pl-4 pr-10 py-2.5 text-sm text-white transition-all outline-none appearance-none cursor-pointer">
                                            <option value="" disabled selected>MM</option>
                                            @for($i=1; $i<=12; $i++)
                                                <option value="{{ $i }}" {{ (isset(Auth::guard('ecommerce')->user()->dob) && \Carbon\Carbon::parse(Auth::guard('ecommerce')->user()->dob)->month == $i) ? 'selected' : '' }}>{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                            @endfor
                                        </select>
                                        <i class="ph ph-caret-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none"></i>
                                    </div>
                                    <div class="relative">
                                        <select name="dob_year" class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl pl-4 pr-10 py-2.5 text-sm text-white transition-all outline-none appearance-none cursor-pointer">
                                            <option value="" disabled selected>YYYY</option>
                                            @for($i=date('Y'); $i>=1900; $i--)
                                                <option value="{{ $i }}" {{ (isset(Auth::guard('ecommerce')->user()->dob) && \Carbon\Carbon::parse(Auth::guard('ecommerce')->user()->dob)->year == $i) ? 'selected' : '' }}>{{ $i }}</option>
                                            @endfor
                                        </select>
                                        <i class="ph ph-caret-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-6 flex justify-start">
                                <button type="submit" class="bg-gradient-to-r from-primary to-[#ff8c33] hover:from-[#ff8c33] hover:to-primary text-white px-8 py-3 rounded-xl text-sm font-bold transition-all shadow-[0_0_15px_rgba(255,107,0,0.3)] hover:shadow-[0_0_25px_rgba(255,107,0,0.5)] transform hover:-translate-y-0.5">
                                    Save Changes
                                </button>
                            </div>
                        </div>

                        <!-- Right Side (Profile Picture) -->
                        <div class="md:w-64 flex flex-col items-center justify-center border-b md:border-b-0 md:border-l border-white/10 pb-8 md:pb-0 md:pl-10">
                            <div class="w-32 h-32 rounded-full border-2 border-white/10 overflow-hidden mb-6 bg-black/40 flex items-center justify-center relative group cursor-pointer shadow-xl transition-all hover:border-primary/50">
                                <!-- Fallback user icon if no image -->
                                <i class="ph ph-user text-5xl text-gray-500 group-hover:opacity-0 transition-opacity duration-300"></i>
                                
                                <!-- Hover overlay -->
                                <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col items-center justify-center backdrop-blur-sm">
                                    <i class="ph ph-camera text-2xl text-white mb-1"></i>
                                    <span class="text-[10px] text-white font-bold uppercase tracking-wider">Change</span>
                                </div>
                            </div>
                            
                            <button class="bg-white/5 border border-white/10 hover:border-white/30 hover:bg-white/10 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all w-full text-center">
                                Select Image
                            </button>

                            <p class="text-[11px] text-gray-500 mt-4 text-center leading-relaxed">
                                File size: max. 1 MB<br>
                                File extension: .JPEG, .PNG
                            </p>
                        </div>
                    </form>
                    </div> <!-- END PANE: PROFILE -->

                    <!-- PANE: PURCHASES -->
                    <div id="pane-purchases" class="content-pane hidden">

                    <div class="border-b border-white/10 pb-4 mb-6 relative z-10">
                            <h2 class="text-2xl font-black text-white">Purchases</h2>
                            <p class="text-sm text-gray-400 mt-1">View and track your orders</p>
                        </div>
                        <div class="relative z-10 flex flex-col gap-6">
                            <!-- Tabs -->
                            <div class="flex overflow-x-auto gap-2 sm:gap-6 border-b border-white/10 pb-2 scrollbar-hide">
                                <a href="#" class="text-primary font-bold border-b-2 border-primary pb-2 px-1 sm:px-2 whitespace-nowrap text-sm transition-colors">All</a>
                                <a href="#" class="text-gray-400 hover:text-white font-medium pb-2 px-1 sm:px-2 whitespace-nowrap text-sm transition-colors border-b-2 border-transparent hover:border-white/30">To Pay</a>
                                <a href="#" class="text-gray-400 hover:text-white font-medium pb-2 px-1 sm:px-2 whitespace-nowrap text-sm transition-colors border-b-2 border-transparent hover:border-white/30">To Ship</a>
                                <a href="#" class="text-gray-400 hover:text-white font-medium pb-2 px-1 sm:px-2 whitespace-nowrap text-sm transition-colors border-b-2 border-transparent hover:border-white/30">To Receive</a>
                                <a href="#" class="text-gray-400 hover:text-white font-medium pb-2 px-1 sm:px-2 whitespace-nowrap text-sm transition-colors border-b-2 border-transparent hover:border-white/30">Completed</a>
                                <a href="#" class="text-gray-400 hover:text-white font-medium pb-2 px-1 sm:px-2 whitespace-nowrap text-sm transition-colors border-b-2 border-transparent hover:border-white/30">Cancelled</a>
                                <a href="#" class="text-gray-400 hover:text-white font-medium pb-2 px-1 sm:px-2 whitespace-nowrap text-sm transition-colors border-b-2 border-transparent hover:border-white/30">Return / Refund</a>
                            </div>
                            <!-- Active Order Card -->
                            <div class="bg-[#1a1a1a] border border-white/5 rounded-2xl p-6 shadow-xl transition-all hover:border-primary/30">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-16 h-16 bg-white/5 rounded-xl flex items-center justify-center border border-white/10 shrink-0">
                                            <i class="ph-bold ph-desktop text-3xl text-primary"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-white">Custom Water-Cooled PC Build</h3>
                                            <p class="text-sm text-gray-400">Order #TF-892415 • Placed 2 days ago</p>
                                        </div>
                                    </div>
                                    <div class="text-left md:text-right">
                                        <p class="text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-primary to-[#ff8c33]">₱145,000</p>
                                        <p class="text-xs font-bold text-primary border border-primary/30 bg-primary/10 px-2 py-0.5 rounded mt-1 inline-block uppercase tracking-wider">Building Phase</p>
                                    </div>
                                </div>
                                
                                <!-- Timeline Bar -->
                                <div class="relative pt-6 pb-2">
                                    <!-- Line -->
                                    <div class="absolute top-8 left-[10%] right-[10%] h-1 bg-white/10 rounded-full"></div>
                                    <div class="absolute top-8 left-[10%] w-[40%] h-1 bg-gradient-to-r from-[#ff5100] to-primary rounded-full shadow-[0_0_10px_rgba(255,107,0,0.5)]"></div>
                                    
                                    <div class="flex justify-between relative z-10">
                                        <!-- Step 1 -->
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-primary border-4 border-[#1a1a1a] flex items-center justify-center shadow-[0_0_10px_rgba(255,107,0,0.5)]">
                                                <i class="ph-bold ph-check text-[10px] text-white"></i>
                                            </div>
                                            <span class="text-[10px] text-primary font-bold uppercase tracking-wider text-center hidden sm:block">Order Placed</span>
                                        </div>
                                        <!-- Step 2 -->
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-primary border-4 border-[#1a1a1a] flex items-center justify-center shadow-[0_0_10px_rgba(255,107,0,0.5)]">
                                                <i class="ph-bold ph-check text-[10px] text-white"></i>
                                            </div>
                                            <span class="text-[10px] text-primary font-bold uppercase tracking-wider text-center hidden sm:block">Processing</span>
                                        </div>
                                        <!-- Step 3 (Active) -->
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="w-8 h-8 -mt-1 rounded-full bg-[#1a1a1a] border-4 border-primary flex items-center justify-center relative shadow-[0_0_15px_rgba(255,107,0,0.5)]">
                                                <div class="w-2.5 h-2.5 rounded-full bg-primary animate-pulse"></div>
                                            </div>
                                            <span class="text-[10px] text-white font-bold uppercase tracking-wider text-center hidden sm:block">Building</span>
                                        </div>
                                        <!-- Step 4 -->
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-white/10 border-4 border-[#1a1a1a]"></div>
                                            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider text-center hidden sm:block">Quality Check</span>
                                        </div>
                                        <!-- Step 5 -->
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-white/10 border-4 border-[#1a1a1a]"></div>
                                            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider text-center hidden sm:block">Shipping</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PANE: BANK & CARDS -->
                    <div id="pane-bank-cards" class="content-pane hidden">
                        <div class="border-b border-white/10 pb-4 mb-6 relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black text-white">Bank & Cards</h2>
                                <p class="text-sm text-gray-400 mt-1">Manage your payment methods</p>
                            </div>
                            <button onclick="openModal('add-card-modal')" class="bg-primary hover:bg-[#ff8c33] text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-lg flex items-center gap-2 whitespace-nowrap">
                                <i class="ph-bold ph-plus"></i> Add New Card
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                            @forelse($paymentMethods->whereIn('type', ['credit_card', 'debit_card']) as $card)
                            <div class="relative {{ $card->is_default ? 'bg-gradient-to-br from-[#1a1c29] to-[#0a0b10] border-white/10 shadow-[0_8px_30px_rgba(0,0,0,0.5)]' : 'bg-gradient-to-br from-[#1c2230] to-[#0d121c] border-white/5 hover:border-white/20 shadow-lg' }} border transition-all rounded-2xl p-6 overflow-hidden group">
                                @if($card->is_default)
                                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>
                                <div class="absolute bottom-0 left-0 w-24 h-24 bg-primary/10 rounded-full blur-xl -ml-8 -mb-8 pointer-events-none"></div>
                                @else
                                <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>
                                @endif
                                
                                <div class="flex items-start justify-between mb-8 relative z-10">
                                    @if($card->is_default)
                                    <div class="flex items-center gap-2">
                                        <i class="ph-fill ph-check-circle text-primary text-xl"></i>
                                        <span class="text-xs font-bold text-primary uppercase tracking-wider">Default Card</span>
                                    </div>
                                    @else
                                    <div class="h-6"></div> <!-- Spacer -->
                                    @endif
                                    
                                    @if(strtolower($card->provider) === 'mastercard')
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-red-500 rounded-full opacity-80 mix-blend-screen"></div>
                                        <div class="w-8 h-8 bg-orange-500 rounded-full opacity-80 mix-blend-screen -ml-3"></div>
                                    </div>
                                    @else
                                    <div class="text-2xl font-black italic text-white/80 tracking-tighter">{{ strtoupper($card->provider) }}</div>
                                    @endif
                                </div>
                                
                                <div class="relative z-10">
                                    <p class="text-xl tracking-[0.2em] font-mono {{ $card->is_default ? 'text-white/90' : 'text-white/70' }} mb-4">**** **** **** {{ $card->account_number_mask }}</p>
                                    <div class="flex items-center justify-between">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] {{ $card->is_default ? 'text-gray-400' : 'text-gray-500' }} uppercase tracking-wider">Card Holder</span>
                                            <span class="text-sm font-bold {{ $card->is_default ? 'text-white' : 'text-white/80' }} uppercase">{{ $card->account_name }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] {{ $card->is_default ? 'text-gray-400' : 'text-gray-500' }} uppercase tracking-wider">Expires</span>
                                            <span class="text-sm font-bold {{ $card->is_default ? 'text-white' : 'text-white/80' }}">{{ $card->expiry_date }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hover Actions -->
                                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-4 z-20">
                                    @if(!$card->is_default)
                                    <form action="{{ route('ecommerce.account.payment-methods.set-default', $card->id) }}" method="POST">
                                        @csrf
                                        <button class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors">
                                            Set as Default
                                        </button>
                                    </form>
                                    @endif
                                    <button type="button" onclick="openEditCardModal('{{ $card->id }}', '{{ $card->account_name }}', '{{ $card->expiry_date }}', '{{ route('ecommerce.account.payment-methods.update', $card->id) }}')" class="bg-blue-500/10 hover:bg-blue-500/20 text-blue-500 p-2.5 rounded-lg transition-colors" title="Edit">
                                        <i class="ph-bold ph-pencil-simple text-lg"></i>
                                    </button>
                                    <button type="button" onclick="confirmDeleteModal('{{ route('ecommerce.account.payment-methods.destroy', $card->id) }}')" class="bg-red-500/10 hover:bg-red-500/20 text-red-500 p-2.5 rounded-lg transition-colors" title="Delete">
                                        <i class="ph-bold ph-trash text-lg"></i>
                                    </button>
                                </div>
                            </div>
                            @empty
                            <div class="col-span-1 md:col-span-2 text-center py-8 bg-white/5 rounded-2xl border border-white/5">
                                <i class="ph ph-credit-card text-4xl text-gray-500 mb-2"></i>
                                <p class="text-gray-400 text-sm">No cards added yet.</p>
                            </div>
                            @endforelse
                        </div>

                        <!-- Bank Accounts Section -->
                        <div class="border-b border-white/10 pb-4 mb-6 mt-12 relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-black text-white">Bank Accounts</h3>
                            </div>
                            <button onclick="openModal('add-bank-modal')" class="bg-[#1a1a1a] hover:bg-white/10 border border-white/10 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-lg flex items-center gap-2 whitespace-nowrap">
                                <i class="ph-bold ph-bank"></i> Add Bank Account
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                            @forelse($paymentMethods->where('type', 'bank_account') as $bank)
                            <div class="relative {{ $bank->is_default ? 'bg-gradient-to-br from-[#1a1c29] to-[#0a0b10] border-white/10 shadow-[0_8px_30px_rgba(0,0,0,0.5)]' : 'bg-[#13131a] border-white/5 hover:border-white/10 shadow-lg' }} border transition-all rounded-2xl p-6 overflow-hidden group">
                                @if($bank->is_default)
                                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>
                                <div class="absolute bottom-0 left-0 w-24 h-24 bg-primary/10 rounded-full blur-xl -ml-8 -mb-8 pointer-events-none"></div>
                                @endif
                                
                                <div class="flex items-start justify-between mb-6 relative z-10">
                                    @if($bank->is_default)
                                    <div class="flex items-center gap-2">
                                        <i class="ph-fill ph-check-circle text-primary text-xl"></i>
                                        <span class="text-xs font-bold text-primary uppercase tracking-wider">Default</span>
                                    </div>
                                    @else
                                    <div class="flex items-center gap-2">
                                        <i class="ph-fill ph-bank text-gray-500 text-xl"></i>
                                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Bank</span>
                                    </div>
                                    @endif
                                    
                                    <div class="text-lg font-black italic text-white/90 tracking-tight">{{ $bank->provider }}</div>
                                </div>
                                
                                <div class="relative z-10">
                                    <p class="text-lg font-mono {{ $bank->is_default ? 'text-white/90' : 'text-white/70' }} mb-2">**** **** {{ $bank->account_number_mask }}</p>
                                    <div class="flex items-center justify-between">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] {{ $bank->is_default ? 'text-gray-400' : 'text-gray-500' }} uppercase tracking-wider">Account Name</span>
                                            <span class="text-sm font-bold {{ $bank->is_default ? 'text-white' : 'text-white/80' }} uppercase">{{ $bank->account_name }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hover Actions -->
                                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-4 z-20">
                                    @if(!$bank->is_default)
                                    <form action="{{ route('ecommerce.account.payment-methods.set-default', $bank->id) }}" method="POST">
                                        @csrf
                                        <button class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors">
                                            Set as Default
                                        </button>
                                    </form>
                                    @endif
                                    <button type="button" onclick="confirmDeleteModal('{{ route('ecommerce.account.payment-methods.destroy', $bank->id) }}')" class="bg-red-500/10 hover:bg-red-500/20 text-red-500 p-2.5 rounded-lg transition-colors" title="Delete">
                                        <i class="ph-bold ph-trash text-lg"></i>
                                    </button>
                                </div>
                            </div>
                            @empty
                            <div class="col-span-1 md:col-span-2 text-center py-8 bg-white/5 rounded-2xl border border-white/5">
                                <i class="ph ph-bank text-4xl text-gray-500 mb-2"></i>
                                <p class="text-gray-400 text-sm">No bank accounts added yet.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- PANE: ADDRESSES -->
                    <div id="pane-addresses" class="content-pane hidden">
                        <div class="border-b border-white/10 pb-4 mb-6 relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black text-white">Addresses</h2>
                                <p class="text-sm text-gray-400 mt-1">Manage your delivery addresses</p>
                            </div>
                            <button onclick="openAddAddressModal()" class="bg-primary hover:bg-[#ff8c33] text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-lg flex items-center gap-2 whitespace-nowrap">
                                <i class="ph-bold ph-plus"></i> Add New Address
                            </button>
                        </div>
                        
                        <div class="flex flex-col gap-4 mt-4">
                            @forelse($addresses as $address)
                            <div class="{{ $address->is_default ? 'bg-[#13131a] border-primary/30 shadow-[0_4px_20px_rgba(255,107,0,0.05)]' : 'bg-[#1a1a1a] border-white/5 hover:border-white/10' }} border rounded-xl p-5 md:p-6 transition-all group">
                                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="text-lg font-bold text-white">{{ $address->full_name }}</h3>
                                            <span class="text-gray-400">|</span>
                                            <span class="text-sm text-gray-300">{{ $address->phone_number }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 mb-2">
                                            @if($address->is_default)
                                            <span class="text-[10px] font-bold text-primary border border-primary/30 bg-primary/10 px-2 py-0.5 rounded uppercase tracking-wider">Default</span>
                                            @endif
                                            <span class="text-[10px] font-bold text-gray-400 border border-white/10 bg-white/5 px-2 py-0.5 rounded uppercase tracking-wider">{{ $address->label }}</span>
                                        </div>
                                        <p class="text-sm text-gray-400 leading-relaxed max-w-2xl mt-3">
                                            {{ $address->detailed_address }}<br>
                                            Brgy. {{ $address->barangay }}, {{ $address->city }}, {{ $address->province === 'Metro Manila' ? 'Metro Manila' : $address->province }}, {{ $address->postal_code }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-start gap-4 md:items-end">
                                        <div class="flex items-center gap-4">
                                            <!-- Edit Address -->
                                            <button type="button" onclick="openEditAddressModal({{ $address->toJson() }})" class="{{ $address->is_default ? 'text-primary hover:text-[#ff8c33]' : 'text-blue-400 hover:text-blue-300' }} p-1 text-lg transition-colors" title="Edit">
                                                <i class="ph-bold ph-pencil-simple"></i>
                                            </button>
                                            
                                            @if(!$address->is_default)
                                            <form action="{{ route('ecommerce.account.addresses.destroy', $address->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-400 hover:text-red-300 text-sm font-bold transition-colors">Delete</button>
                                            </form>
                                            @endif
                                        </div>
                                        
                                        @if(!$address->is_default)
                                        <form action="{{ route('ecommerce.account.addresses.set-default', $address->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-gray-400 hover:text-white text-xs font-bold transition-colors border border-white/10 px-3 py-1.5 rounded-lg hover:border-white/30">Set as Default</button>
                                        </form>
                                        @else
                                        <button class="hidden md:block text-white/20 hover:text-white transition-colors cursor-not-allowed" disabled>Set as Default</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-8 bg-white/5 rounded-2xl border border-white/5">
                                <i class="ph ph-map-pin text-4xl text-gray-500 mb-2"></i>
                                <p class="text-gray-400 text-sm">No addresses added yet.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- PANE: PASSWORD -->
                    <div id="pane-password" class="content-pane hidden">
                        <div class="border-b border-white/10 pb-4 mb-8 relative z-10">
                            <h2 class="text-2xl font-black text-white">Change Password</h2>
                            <p class="text-sm text-gray-400 mt-1">Update your account security</p>
                        </div>
                        <div class="py-10 max-w-md mx-auto">
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm text-gray-400 font-medium shrink-0">Current Password</label>
                                    <input type="password" placeholder="••••••••" class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-2.5 text-sm text-white transition-all outline-none placeholder-gray-600">
                                </div>
                                <div class="flex flex-col gap-2 mt-4 relative">
                                    <label class="text-sm text-gray-400 font-medium shrink-0">New Password</label>
                                    <input type="password" id="new-password-input" placeholder="••••••••" class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-2.5 text-sm text-white transition-all outline-none placeholder-gray-600">
                                    
                                    <!-- Password Strength Meter -->
                                    <div class="mt-2 flex flex-col gap-2">
                                        <div class="flex gap-1 h-1.5 w-full">
                                            <div id="pw-strength-1" class="h-full flex-1 rounded-full bg-white/10 transition-colors duration-300"></div>
                                            <div id="pw-strength-2" class="h-full flex-1 rounded-full bg-white/10 transition-colors duration-300"></div>
                                            <div id="pw-strength-3" class="h-full flex-1 rounded-full bg-white/10 transition-colors duration-300"></div>
                                        </div>
                                        <div class="flex flex-wrap gap-4 mt-1">
                                            <span id="pw-req-len" class="text-[10px] flex items-center gap-1 text-gray-500 transition-colors"><i class="ph-fill ph-check-circle transition-colors"></i> 8+ characters</span>
                                            <span id="pw-req-up" class="text-[10px] flex items-center gap-1 text-gray-500 transition-colors"><i class="ph-fill ph-check-circle transition-colors"></i> 1 uppercase</span>
                                            <span id="pw-req-sym" class="text-[10px] flex items-center gap-1 text-gray-500 transition-colors"><i class="ph-fill ph-check-circle transition-colors"></i> 1 symbol</span>
                                        </div>
                                    </div>
                                    <script>
                                        document.getElementById('new-password-input')?.addEventListener('input', function(e) {
                                            const val = e.target.value;
                                            let strength = 0;
                                            
                                            const hasLen = val.length >= 8;
                                            const hasUp = /[A-Z]/.test(val);
                                            const hasSym = /[^A-Za-z0-9]/.test(val);
                                            
                                            // Update req list
                                            document.getElementById('pw-req-len').className = `text-[10px] flex items-center gap-1 transition-colors ${hasLen ? 'text-green-400' : 'text-gray-500'}`;
                                            document.getElementById('pw-req-up').className = `text-[10px] flex items-center gap-1 transition-colors ${hasUp ? 'text-green-400' : 'text-gray-500'}`;
                                            document.getElementById('pw-req-sym').className = `text-[10px] flex items-center gap-1 transition-colors ${hasSym ? 'text-green-400' : 'text-gray-500'}`;
                                            
                                            if(hasLen) strength++;
                                            if(hasUp) strength++;
                                            if(hasSym) strength++;
                                            
                                            // Update bars
                                            document.getElementById('pw-strength-1').className = `h-full flex-1 rounded-full transition-colors duration-300 ${strength >= 1 ? 'bg-red-500' : 'bg-white/10'}`;
                                            document.getElementById('pw-strength-2').className = `h-full flex-1 rounded-full transition-colors duration-300 ${strength >= 2 ? 'bg-yellow-500' : 'bg-white/10'}`;
                                            document.getElementById('pw-strength-3').className = `h-full flex-1 rounded-full transition-colors duration-300 ${strength >= 3 ? 'bg-green-500' : 'bg-white/10'}`;
                                        });
                                    </script>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm text-gray-400 font-medium shrink-0">Confirm New Password</label>
                                    <input type="password" placeholder="••••••••" class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-2.5 text-sm text-white transition-all outline-none placeholder-gray-600">
                                </div>
                                <button class="mt-6 bg-gradient-to-r from-primary to-[#ff8c33] hover:from-[#ff8c33] hover:to-primary text-white w-full py-3 rounded-xl text-sm font-bold transition-all shadow-[0_0_15px_rgba(255,107,0,0.3)] hover:shadow-[0_0_25px_rgba(255,107,0,0.5)] transform hover:-translate-y-0.5">
                                    Update Password
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- PANE: VOUCHERS -->
                    <div id="pane-vouchers" class="content-pane hidden">

                    <div class="border-b border-white/10 pb-4 mb-6 relative z-10">
                            <h2 class="text-2xl font-black text-white">My Vouchers</h2>
                            <p class="text-sm text-gray-400 mt-1">Manage and use your discount codes</p>
                        </div>
                        
                        <!-- Add Voucher Input -->
                        <div class="bg-[#13131a]/50 border border-white/5 rounded-xl p-5 md:p-6 mb-8 shadow-inner flex flex-col md:flex-row items-center gap-4">
                            <h3 class="text-white font-bold whitespace-nowrap flex items-center"><i class="ph-bold ph-plus-circle text-primary text-xl mr-2"></i>Add Voucher</h3>
                            <div class="flex items-center w-full gap-2">
                                <input type="text" placeholder="Enter voucher code" class="flex-1 bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-3 text-sm text-white transition-all outline-none placeholder-gray-600 font-mono uppercase tracking-widest">
                                <button class="bg-primary hover:bg-[#ff8c33] text-white px-6 py-3 rounded-xl text-sm font-bold transition-all shadow-lg whitespace-nowrap">
                                    Apply
                                </button>
                            </div>
                        </div>

                        <!-- Active Vouchers List -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Physical Voucher Ticket Design 1 -->
                            <div class="relative flex bg-[#1a1a1a] border border-white/5 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.4)] transition-all hover:border-primary/30 group overflow-hidden">
                                
                                <!-- Left Logo/Color Section -->
                                <div class="w-24 bg-gradient-to-br from-[#ff5100] to-primary flex flex-col items-center justify-center border-r-[3px] border-dashed border-[#1a1a1a] relative shrink-0">
                                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center mb-1 backdrop-blur-sm shadow-inner">
                                        <i class="ph-bold ph-gift text-xl text-white"></i>
                                    </div>
                                    <span class="text-[10px] font-black text-white text-center uppercase tracking-widest mt-1">REWARD</span>
                                    
                                    <!-- Semi-circle Cutouts -->
                                    <div class="absolute -top-3 -right-[11px] w-5 h-5 bg-[#0a0a0a] rounded-full border border-white/5 z-10"></div>
                                    <div class="absolute -bottom-3 -right-[11px] w-5 h-5 bg-[#0a0a0a] rounded-full border border-white/5 z-10"></div>
                                </div>
                                
                                <!-- Right Details Section -->
                                <div class="flex-1 p-4 flex flex-col justify-between relative">
                                    <div class="flex flex-col">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[9px] font-bold text-primary border border-primary/30 bg-primary/10 px-1.5 py-0.5 rounded uppercase tracking-wider">New User</span>
                                            <a href="#" class="text-[10px] text-blue-400 hover:text-blue-300 hover:underline transition-colors">T&C</a>
                                        </div>
                                        <h4 class="text-base font-black text-white mb-0.5 leading-tight">20% OFF DISCOUNT</h4>
                                        <p class="text-xs text-gray-400">Min. Spend ₱0 • Max ₱2,000</p>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-4">
                                        <div class="flex items-center gap-1.5 text-[10px] font-medium text-[#ff5100] bg-[#ff5100]/10 px-2 py-1 rounded border border-[#ff5100]/20">
                                            <i class="ph-fill ph-clock"></i> 7 days left
                                        </div>
                                        
                                        <button class="bg-transparent border border-primary text-primary hover:bg-primary hover:text-white hover:shadow-[0_0_15px_rgba(255,107,0,0.4)] px-4 py-1.5 rounded-lg text-xs font-black transition-all uppercase tracking-wider">
                                            Use
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Physical Voucher Ticket Design 2 (Duplicate for demo) -->
                            <div class="relative flex bg-[#1a1a1a] border border-white/5 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.4)] transition-all hover:border-primary/30 group overflow-hidden opacity-50 grayscale hover:grayscale-0 hover:opacity-100 cursor-not-allowed">
                                
                                <!-- Left Logo/Color Section -->
                                <div class="w-24 bg-gradient-to-br from-[#ff5100] to-primary flex flex-col items-center justify-center border-r-[3px] border-dashed border-[#1a1a1a] relative shrink-0">
                                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center mb-1 backdrop-blur-sm shadow-inner">
                                        <i class="ph-bold ph-truck text-xl text-white"></i>
                                    </div>
                                    <span class="text-[10px] font-black text-white text-center uppercase tracking-widest mt-1">SHIPPING</span>
                                    
                                    <!-- Semi-circle Cutouts -->
                                    <div class="absolute -top-3 -right-[11px] w-5 h-5 bg-[#0a0a0a] rounded-full border border-white/5 z-10"></div>
                                    <div class="absolute -bottom-3 -right-[11px] w-5 h-5 bg-[#0a0a0a] rounded-full border border-white/5 z-10"></div>
                                </div>
                                
                                <!-- Right Details Section -->
                                <div class="flex-1 p-4 flex flex-col justify-between relative">
                                    <div class="flex flex-col">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[9px] font-bold text-gray-400 border border-white/10 bg-white/5 px-1.5 py-0.5 rounded uppercase tracking-wider">Expired</span>
                                            <a href="#" class="text-[10px] text-blue-400 hover:text-blue-300 hover:underline transition-colors pointer-events-auto">T&C</a>
                                        </div>
                                        <h4 class="text-base font-black text-white mb-0.5 leading-tight">FREE SHIPPING</h4>
                                        <p class="text-xs text-gray-400">Min. Spend ₱5,000</p>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-4">
                                        <div class="flex items-center gap-1.5 text-[10px] font-medium text-gray-500 bg-white/5 px-2 py-1 rounded border border-white/10">
                                            <i class="ph-fill ph-x-circle"></i> Expired
                                        </div>
                                        
                                        <button disabled class="bg-white/5 border border-white/10 text-gray-500 px-4 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider cursor-not-allowed">
                                            Use
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State (Hidden when vouchers exist) -->
                        <div class="hidden py-10 flex-col items-center justify-center text-center">
                            <div class="w-24 h-24 bg-white/5 rounded-full flex items-center justify-center mb-6 shadow-inner">
                                <i class="ph ph-ticket text-5xl text-gray-600"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2">No active vouchers</h3>
                            <p class="text-gray-400 text-sm max-w-sm">You don't have any vouchers available right now. Keep an eye out for special promotions!</p>
                        </div>
                    </div>

                    <!-- PANE: FORGE POINTS -->
                    <div id="pane-forge-points" class="content-pane hidden">
                        <div class="border-b border-white/10 pb-4 mb-8 relative z-10">
                            <h2 class="text-2xl font-black text-white">Forge Points</h2>
                            <p class="text-sm text-gray-400 mt-1">Your reward balance</p>
                        </div>
                        <div class="py-10 flex flex-col items-center justify-center text-center">
                            <div class="relative w-32 h-32 flex items-center justify-center mb-6">
                                <!-- Glow -->
                                <div class="absolute inset-0 bg-primary/20 rounded-full blur-xl"></div>
                                <div class="absolute inset-0 rounded-full border-[6px] border-white/5"></div>
                                <!-- Progress arc (fake with clip/rotate) -->
                                <svg class="absolute inset-0 w-full h-full -rotate-90 transform overflow-visible" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="46" fill="none" stroke="url(#forge-gradient)" stroke-width="8" stroke-dasharray="289" stroke-dashoffset="144" class="transition-all duration-1000 ease-out drop-shadow-[0_0_8px_rgba(255,107,0,0.6)]"></circle>
                                    <defs>
                                        <linearGradient id="forge-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" stop-color="#ff5100" />
                                            <stop offset="100%" stop-color="#ff8c33" />
                                        </linearGradient>
                                    </defs>
                                </svg>
                                
                                <div class="w-24 h-24 bg-gradient-to-br from-[#1a1a1a] to-[#0a0a0a] rounded-full flex flex-col items-center justify-center border-2 border-white/10 relative z-10">
                                    <i class="ph-fill ph-coins text-3xl text-primary mb-1 shadow-[0_0_15px_rgba(255,107,0,0.5)]"></i>
                                </div>
                            </div>
                            <h3 class="text-4xl font-black text-white mb-1 tracking-tight">500 <span class="text-primary text-xl">FP</span></h3>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Current Tier:</span>
                                <span class="text-xs font-black text-transparent bg-clip-text bg-gradient-to-r from-gray-400 to-gray-100 uppercase tracking-widest border border-white/20 bg-white/5 px-2 py-0.5 rounded shadow-sm">Bronze</span>
                            </div>
                            
                            <div class="w-full max-w-md mt-10">
                                <div class="flex justify-between text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">
                                    <span>Bronze (0)</span>
                                    <span class="text-white">Silver (1,000)</span>
                                    <span>Gold (5,000)</span>
                                </div>
                                <div class="h-2 w-full bg-white/10 rounded-full relative overflow-hidden">
                                    <div class="absolute top-0 left-0 h-full w-1/2 bg-gradient-to-r from-[#ff5100] to-primary rounded-full shadow-[0_0_10px_rgba(255,107,0,0.5)]"></div>
                                </div>
                                <p class="text-xs text-primary mt-3 font-medium">Earn 500 more FP to unlock Silver Tier rewards!</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Javascript for SPA Navigation & Accordion -->
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const sidebarLinks = document.querySelectorAll('.sidebar-link');
                        const panes = document.querySelectorAll('.content-pane');

                        // Function to open a pane and update sidebar state
                        function openPane(targetId, updateHistory = true) {
                            // 1. Hide all panes
                            panes.forEach(pane => {
                                pane.classList.remove('block');
                                pane.classList.add('hidden');
                            });
                            // 2. Show target pane
                            const targetPane = document.getElementById(targetId);
                            if (targetPane) {
                                targetPane.classList.remove('hidden');
                                targetPane.classList.add('block');
                            }

                            // 3. Update active states on sidebar links
                            sidebarLinks.forEach(link => {
                                link.classList.remove('active', 'text-primary');
                                link.classList.add('text-gray-400');
                                
                                // Restore hover effects for inactive links
                                link.classList.add('hover:text-white');
                                link.classList.remove('hover:text-primary');
                                
                                // Ensure icon styling tracks with active state if it's a main link
                                const icon = link.querySelector('.category-icon');
                                if (icon) icon.classList.remove('text-primary');

                                if (link.getAttribute('data-target') === targetId) {
                                    link.classList.remove('text-gray-400');
                                    link.classList.add('active', 'text-primary');
                                    
                                    // Remove hover effects for active link so it stays primary
                                    link.classList.remove('hover:text-white');
                                    link.classList.add('hover:text-primary');
                                    
                                    if (icon) icon.classList.add('text-primary');
                                }
                            });

                            // 4. Update URL conditionally
                            if (updateHistory) {
                                let path = '/account/profile';
                                if (targetId === 'pane-purchases') path = '/account/purchases';
                                window.history.pushState({pane: targetId}, '', path + '#' + targetId.replace('pane-', ''));
                            }
                        }

                        // Attach event listeners to all links
                        sidebarLinks.forEach(link => {
                            link.addEventListener('click', (e) => {
                                e.preventDefault();
                                const targetId = link.getAttribute('data-target');
                                
                                if (link.classList.contains('main-category-link')) {
                                    // If clicking a main category (Purchases, Vouchers, etc.)
                                    document.querySelectorAll('.category-dropdown').forEach(dropdown => {
                                        dropdown.style.maxHeight = '0px';
                                        dropdown.classList.remove('pb-2', 'py-1', 'mt-2', 'border-white/10');
                                        dropdown.classList.add('opacity-0', 'border-transparent');
                                    });
                                } else if (link.classList.contains('account-details-link')) {
                                    // If clicking Account Details itself
                                    const dropdownId = link.getAttribute('data-dropdown');
                                    const dropdown = document.getElementById(dropdownId);
                                    if (dropdown) {
                                        dropdown.style.maxHeight = '500px';
                                        dropdown.classList.add('pb-2', 'py-1', 'mt-2', 'border-white/10');
                                        dropdown.classList.remove('opacity-0', 'border-transparent');
                                    }
                                } else {
                                    // Make sure its parent dropdown is open (if navigating via URL hash/history)
                                    const parentDropdown = link.closest('.category-dropdown');
                                    if (parentDropdown && parentDropdown.style.maxHeight === '0px') {
                                        parentDropdown.style.maxHeight = '500px';
                                        parentDropdown.classList.add('pb-2', 'py-1', 'mt-2', 'border-white/10');
                                        parentDropdown.classList.remove('opacity-0', 'border-transparent');
                                    }
                                }

                                openPane(targetId);
                            });
                        });

                        // Handle initial load based on hash or path
                        const hash = window.location.hash.replace('#', '');
                        const path = window.location.pathname;
                        
                        if (hash && document.getElementById('pane-' + hash)) {
                            openPane('pane-' + hash, false);
                            
                            // Make sure dropdown is open if we selected an item inside it
                            const activeLink = document.querySelector('[data-target="pane-' + hash + '"]');
                            if (activeLink && activeLink.closest('.category-dropdown')) {
                                const dd = activeLink.closest('.category-dropdown');
                                dd.style.maxHeight = '500px';
                                dd.classList.add('border-white/10', 'mt-2');
                                dd.classList.remove('opacity-0', 'border-transparent');
                            }
                        } else if (path.includes('/purchases')) {
                            openPane('pane-purchases', false);
                            // Close the account details dropdown by default
                            document.querySelectorAll('.category-dropdown').forEach(dropdown => {
                                dropdown.style.maxHeight = '0px';
                                dropdown.classList.remove('py-1', 'mt-2', 'border-white/10');
                                dropdown.classList.add('opacity-0', 'border-transparent');
                            });
                        } else {
                            // Ensure the dropdown is fully visible by default on profile page
                            document.querySelectorAll('.category-dropdown').forEach(dropdown => {
                                dropdown.style.maxHeight = '500px';
                                dropdown.classList.add('border-white/10', 'mt-2');
                                dropdown.classList.remove('opacity-0', 'border-transparent');
                            });
                        }
                    });
                </script>

            </div>
        </div>
    </main>
                <!-- Modals -->
                <!-- Add Card Modal -->
                <div id="add-card-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
                    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal('add-card-modal')"></div>
                    <div class="relative bg-[#13131a] border border-white/10 rounded-2xl p-6 md:p-8 w-full max-w-lg shadow-[0_0_50px_rgba(0,0,0,0.5)] transform scale-95 opacity-0 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <h3 class="text-xl font-black text-white font-mono uppercase tracking-widest">Add New Card</h3>
                                <span class="bg-green-500/10 text-green-500 border border-green-500/20 px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                                    <i class="ph-bold ph-lock-key"></i> 256-bit Secure
                                </span>
                            </div>
                            <button onclick="closeModal('add-card-modal')" class="text-gray-400 hover:text-white transition-colors">
                                <i class="ph-bold ph-x text-xl"></i>
                            </button>
                        </div>
                        <form action="{{ route('ecommerce.account.payment-methods.store-card') }}" method="POST">
                            @csrf
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider font-mono text-primary flex items-center gap-1">
                                        <i class="ph-bold ph-magic-wand"></i> Test Brand Generator
                                    </label>
                                    <div class="relative flex items-center gap-3">
                                        <div id="brand-icon-container" class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
                                            <i class="ph-bold ph-credit-card text-xl"></i>
                                        </div>
                                        <div class="relative flex-1">
                                            <select id="mock_brand_selector" class="w-full bg-primary/10 border border-primary/20 focus:border-primary focus:ring-1 focus:ring-primary rounded-xl px-4 py-2.5 text-sm text-primary font-bold transition-all outline-none appearance-none cursor-pointer font-mono">
                                                <option value="" disabled selected class="bg-[#13131a] text-white">Select a brand to auto-fill prefix</option>
                                                <option value="4" class="bg-[#13131a] text-white" data-icon="ph-cc-visa">Visa</option>
                                                <option value="51" class="bg-[#13131a] text-white" data-icon="ph-cc-mastercard">Mastercard</option>
                                                <option value="35" class="bg-[#13131a] text-white" data-icon="ph-cc-jcb">JCB</option>
                                            </select>
                                            <i class="ph-bold ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-primary pointer-events-none"></i>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="card_type" value="credit_card">
                                <div class="flex flex-col gap-2">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider font-mono">Card Number</label>
                                    <input type="text" id="card_number" name="card_number" placeholder="0000 0000 0000 0000" maxlength="19" required class="w-full bg-black/40 border border-white/10 focus:border-red-500 focus:ring-1 focus:ring-red-500 focus:bg-black/60 rounded-xl px-4 py-3 text-sm text-white transition-all outline-none font-mono">
                                    <span class="error-msg text-red-500 text-xs font-mono font-bold hidden" data-error-for="card_number"></span>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider font-mono">Expiry Date</label>
                                        <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5" required class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-3 text-sm text-white transition-all outline-none font-mono">
                                        <span class="error-msg text-red-500 text-xs font-mono font-bold hidden" data-error-for="expiry_date"></span>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider font-mono">CVV</label>
                                        <input type="password" id="cvv" name="cvv" placeholder="123" maxlength="4" required class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-3 text-sm text-white transition-all outline-none font-mono">
                                        <span class="error-msg text-red-500 text-xs font-mono font-bold hidden" data-error-for="cvv"></span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider font-mono">Cardholder Name</label>
                                    <input type="text" name="cardholder_name" placeholder="JOHN DOE" required class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-3 text-sm text-white transition-all outline-none uppercase font-mono tracking-wider">
                                    <span class="error-msg text-red-500 text-xs font-mono font-bold hidden" data-error-for="cardholder_name"></span>
                                </div>
                                <button type="submit" class="mt-4 bg-gradient-to-r from-primary to-[#ff8c33] hover:from-[#ff8c33] hover:to-primary text-white w-full py-3 rounded-xl text-sm font-bold transition-all shadow-[0_0_15px_rgba(255,107,0,0.3)] hover:shadow-[0_0_25px_rgba(255,107,0,0.5)] flex justify-center items-center gap-2">
                                    <i class="ph-bold ph-lock"></i> Save Card Securely
                                </button>
                                
                                <div class="mt-4 pt-4 border-t border-white/10 flex items-center justify-center gap-6 opacity-60">
                                    <div class="flex items-center gap-2">
                                        <i class="ph-bold ph-shield-check text-2xl text-green-500"></i>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-bold text-white uppercase tracking-wider">PCI-DSS</span>
                                            <span class="text-[9px] text-gray-400">Compliant</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="ph-fill ph-check-circle text-2xl text-blue-500"></i>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-bold text-white uppercase tracking-wider">Verified</span>
                                            <span class="text-[9px] text-gray-400">by Visa & MC</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4 flex justify-center">
                                    <div class="bg-red-500/10 border border-red-500/20 px-3 py-2 rounded-lg flex items-center gap-2">
                                        <i class="ph-fill ph-warning-circle text-red-500 text-sm"></i>
                                        <span class="text-[10px] font-bold text-red-400 uppercase tracking-wider">For test purposes only. Do not enter real card details.</span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const cardNumberInput = document.getElementById('card_number');
                        const expiryDateInput = document.getElementById('expiry_date');
                        const cvvInput = document.getElementById('cvv');

                        if (cardNumberInput) {
                            cardNumberInput.addEventListener('input', function (e) {
                                let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                                let formattedValue = '';
                                for (let i = 0; i < value.length; i++) {
                                    if (i > 0 && i % 4 === 0) {
                                        formattedValue += ' ';
                                    }
                                    formattedValue += value[i];
                                }
                                e.target.value = formattedValue;
                            });
                        }

                        if (expiryDateInput) {
                            expiryDateInput.addEventListener('input', function (e) {
                                let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                                if (value.length >= 2) {
                                    let month = parseInt(value.substring(0, 2), 10);
                                    if (month > 12) month = 12;
                                    if (month === 0 && value.length > 1) month = 1;
                                    let monthStr = value.length > 1 ? month.toString().padStart(2, '0') : value;
                                    value = monthStr + (value.length > 2 ? '/' + value.substring(2, 4) : '');
                                }
                                e.target.value = value;
                            });
                        }

                        if (cvvInput) {
                            cvvInput.addEventListener('input', function (e) {
                                e.target.value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                            });
                        }

                        const mockBrandSelector = document.getElementById('mock_brand_selector');
                        if (mockBrandSelector && cardNumberInput) {
                            mockBrandSelector.addEventListener('change', (e) => {
                                cardNumberInput.value = e.target.value;
                                // Trigger input event to re-format
                                cardNumberInput.dispatchEvent(new Event('input', { bubbles: true }));
                                cardNumberInput.focus();

                                // Update icon
                                const selectedOption = e.target.options[e.target.selectedIndex];
                                const iconClass = selectedOption.getAttribute('data-icon');
                                const iconContainer = document.getElementById('brand-icon-container');
                                if (iconContainer && iconClass) {
                                    iconContainer.innerHTML = `<i class="ph-bold ${iconClass} text-xl"></i>`;
                                }
                            });
                        }
                    });
                </script>

                <!-- Add Bank Account Modal -->
                <div id="add-bank-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center">

                    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal('add-bank-modal')"></div>
                    <div class="relative bg-[#13131a] border border-white/10 rounded-2xl p-6 md:p-8 w-full max-w-lg shadow-[0_0_50px_rgba(0,0,0,0.5)] transform scale-95 opacity-0 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-black text-white font-mono uppercase tracking-widest">Add Bank Account</h3>
                            <button onclick="closeModal('add-bank-modal')" class="text-gray-400 hover:text-white transition-colors">
                                <i class="ph-bold ph-x text-xl"></i>
                            </button>
                        </div>
                        <form action="{{ route('ecommerce.account.payment-methods.store-bank') }}" method="POST">
                            @csrf
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider font-mono">Bank Provider</label>
                                    <div class="relative">
                                        <select name="provider" required class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-3 text-sm text-white transition-all outline-none appearance-none cursor-pointer font-mono">
                                            <option value="" disabled selected class="bg-[#13131a] text-white">Select your bank</option>
                                            <option value="BDO Unibank" class="bg-[#13131a] text-white">BDO Unibank</option>
                                            <option value="BPI" class="bg-[#13131a] text-white">BPI (Bank of the Philippine Islands)</option>
                                            <option value="UnionBank" class="bg-[#13131a] text-white">UnionBank of the Philippines</option>
                                            <option value="Metrobank" class="bg-[#13131a] text-white">Metrobank</option>
                                            <option value="GCash" class="bg-[#13131a] text-white">GCash</option>
                                            <option value="Maya" class="bg-[#13131a] text-white">Maya</option>
                                        </select>
                                        <i class="ph-bold ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider font-mono">Account Name</label>
                                    <input type="text" name="account_name" placeholder="JOHN DOE" required class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-3 text-sm text-white transition-all outline-none uppercase font-mono tracking-wider">
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider font-mono">Account Number</label>
                                    <input type="text" name="account_number" placeholder="0000 0000 0000" required class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-3 text-sm text-white transition-all outline-none font-mono">
                                </div>
                                <button type="submit" class="mt-4 bg-[#1a1a1a] hover:bg-white/10 border border-white/10 text-white w-full py-3 rounded-xl text-sm font-bold transition-all shadow-[0_0_15px_rgba(255,107,0,0.1)] hover:shadow-[0_0_25px_rgba(255,255,255,0.1)]">
                                    Save Bank Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Success Notification Modal -->
                @if (session('success'))
                <div id="success-modal" class="fixed inset-0 z-[9999] flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal('success-modal')"></div>
                    <div class="relative bg-[#13131a] border border-primary/30 rounded-2xl p-8 w-full max-w-sm shadow-[0_0_50px_rgba(255,107,0,0.3)] transform scale-100 opacity-100 transition-all duration-300 text-center flex flex-col items-center">
                        <div class="w-16 h-16 rounded-full bg-primary/20 flex items-center justify-center mb-4 shadow-[0_0_20px_rgba(255,107,0,0.4)]">
                            <i class="ph-bold ph-check text-3xl text-primary"></i>
                        </div>
                        <h3 class="text-xl font-black text-white mb-2 font-mono uppercase tracking-widest">Success!</h3>
                        <p class="text-sm text-gray-400 mb-6 font-mono">{{ session('success') }}</p>
                        <button onclick="closeModal('success-modal')" class="bg-primary hover:bg-[#ff8c33] text-white px-8 py-2.5 rounded-xl text-sm font-bold transition-all shadow-[0_0_15px_rgba(255,107,0,0.3)] hover:shadow-[0_0_25px_rgba(255,107,0,0.5)] w-full font-mono uppercase">
                            Close
                        </button>
                    </div>
                </div>
                @endif

                <!-- Edit Card Modal -->
                <div id="edit-card-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
                    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal('edit-card-modal')"></div>
                    <div class="relative bg-[#13131a] border border-white/10 rounded-2xl p-6 md:p-8 w-full max-w-lg shadow-[0_0_50px_rgba(0,0,0,0.5)] transform scale-95 opacity-0 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-black text-white font-mono uppercase tracking-widest">Edit Card Details</h3>
                            <button onclick="closeModal('edit-card-modal')" class="text-gray-400 hover:text-white transition-colors">
                                <i class="ph-bold ph-x text-xl"></i>
                            </button>
                        </div>
                        <form action="" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider font-mono">Card Holder Name</label>
                                    <input type="text" id="edit_cardholder_name" name="cardholder_name" required class="w-full bg-black/40 border border-white/10 focus:border-red-500 focus:ring-1 focus:ring-red-500 focus:bg-black/60 rounded-xl px-4 py-3 text-sm text-white transition-all outline-none font-mono">
                                    <span class="error-msg text-red-500 text-xs font-mono font-bold hidden" data-error-for="cardholder_name"></span>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider font-mono">Expiry Date</label>
                                    <input type="text" id="edit_expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5" required class="w-full bg-black/40 border border-white/10 focus:border-red-500 focus:ring-1 focus:ring-red-500 focus:bg-black/60 rounded-xl px-4 py-3 text-sm text-white transition-all outline-none font-mono">
                                    <span class="error-msg text-red-500 text-xs font-mono font-bold hidden" data-error-for="expiry_date"></span>
                                </div>
                                <div class="mt-4 pt-4 border-t border-white/10">
                                    <button type="submit" class="bg-primary hover:bg-[#ff8c33] text-white px-8 py-3 rounded-xl text-sm font-bold transition-all w-full flex items-center justify-center gap-2 shadow-[0_0_15px_rgba(255,107,0,0.3)] hover:shadow-[0_0_25px_rgba(255,107,0,0.5)] font-mono uppercase tracking-wider">
                                        <i class="ph-bold ph-floppy-disk"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div id="delete-confirmation-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
                    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal('delete-confirmation-modal')"></div>
                    <div class="relative bg-[#13131a] border border-red-500/30 rounded-2xl p-8 w-full max-w-sm shadow-[0_0_50px_rgba(239,68,68,0.3)] transform scale-95 opacity-0 transition-all duration-300 text-center flex flex-col items-center">
                        <div class="w-16 h-16 rounded-full bg-red-500/20 flex items-center justify-center mb-4 shadow-[0_0_20px_rgba(239,68,68,0.4)]">
                            <i class="ph-bold ph-warning text-3xl text-red-500"></i>
                        </div>
                        <h3 class="text-xl font-black text-white mb-2 font-mono uppercase tracking-widest">Remove Method?</h3>
                        <p class="text-sm text-gray-400 mb-6 font-mono">Are you sure you want to delete this payment method? This cannot be undone.</p>
                        
                        <form action="" method="POST" class="w-full">
                            @csrf
                            @method('DELETE')
                            <div class="flex gap-4">
                                <button type="button" onclick="closeModal('delete-confirmation-modal')" class="bg-white/5 hover:bg-white/10 border border-white/10 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all w-full font-mono uppercase">
                                    Cancel
                                </button>
                                <button type="submit" class="bg-red-500 hover:bg-red-400 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all shadow-[0_0_15px_rgba(239,68,68,0.3)] hover:shadow-[0_0_25px_rgba(239,68,68,0.5)] w-full font-mono uppercase">
                                    Delete
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Error Notification Modal -->
                <div id="error-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
                    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal('error-modal')"></div>
                    <div class="relative bg-[#13131a] border border-red-500/30 rounded-2xl p-8 w-full max-w-sm shadow-[0_0_50px_rgba(239,68,68,0.3)] transform scale-95 opacity-0 transition-all duration-300 text-center flex flex-col items-center">
                        <div class="w-16 h-16 rounded-full bg-red-500/20 flex items-center justify-center mb-4 shadow-[0_0_20px_rgba(239,68,68,0.4)]">
                            <i class="ph-bold ph-x text-3xl text-red-500"></i>
                        </div>
                        <h3 class="text-xl font-black text-white mb-2 font-mono uppercase tracking-widest">Wait a minute!</h3>
                        <p class="text-sm text-gray-400 mb-6 font-mono">{{ session('error', '') }}</p>
                        <button onclick="closeModal('error-modal')" class="bg-red-500 hover:bg-red-400 text-white px-8 py-2.5 rounded-xl text-sm font-bold transition-all shadow-[0_0_15px_rgba(239,68,68,0.3)] hover:shadow-[0_0_25px_rgba(239,68,68,0.5)] w-full font-mono uppercase">
                            Close
                        </button>
                    </div>
                </div>

                <!-- Add Address Modal -->
                <div id="add-address-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
                    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal('add-address-modal')"></div>
                    <div class="relative bg-[#13131a] border border-white/10 rounded-2xl p-5 w-full max-w-2xl shadow-[0_0_50px_rgba(0,0,0,0.5)] transform scale-95 opacity-0 transition-all duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-black text-white">Add New Address</h3>
                            <button type="button" onclick="closeModal('add-address-modal')" class="text-gray-400 hover:text-white transition-colors">
                                <i class="ph-bold ph-x text-xl"></i>
                            </button>
                        </div>
                        <form id="addAddressForm" action="{{ route('ecommerce.account.addresses.store') }}" method="POST">
                            @csrf
                            <input type="hidden" id="latitude" name="latitude" value="">
                            <input type="hidden" id="longitude" name="longitude" value="">

                            <div class="flex flex-col gap-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Full Name</label>
                                        <input type="text" name="full_name" required placeholder="John Doe" class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-2 text-sm text-white transition-all outline-none">
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Phone Number</label>
                                        <input type="text" name="phone_number" required placeholder="(+63) 912 345 6789" class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-2 text-sm text-white transition-all outline-none font-mono">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="flex flex-col gap-1 relative">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Region</label>
                                        <select id="region_code" required class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-2 text-sm text-white transition-all outline-none appearance-none cursor-pointer">
                                            <option value="" disabled selected class="bg-[#13131a] text-white">Select Region</option>
                                        </select>
                                        <i class="ph ph-caret-down absolute right-4 top-[32px] text-gray-500 pointer-events-none"></i>
                                        <input type="hidden" name="region" id="region_name">
                                    </div>
                                    <div class="flex flex-col gap-1 relative">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Province</label>
                                        <select id="province_code" required disabled class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-2 text-sm text-white transition-all outline-none appearance-none disabled:opacity-50">
                                            <option value="" disabled selected class="bg-[#13131a] text-white">Select Province</option>
                                        </select>
                                        <i class="ph ph-caret-down absolute right-4 top-[32px] text-gray-500 pointer-events-none"></i>
                                        <input type="hidden" name="province" id="province_name">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="flex flex-col gap-1 relative">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">City / Municipality</label>
                                        <select id="city_code" required disabled class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-2 text-sm text-white transition-all outline-none appearance-none disabled:opacity-50">
                                            <option value="" disabled selected class="bg-[#13131a] text-white">Select City</option>
                                        </select>
                                        <i class="ph ph-caret-down absolute right-4 top-[32px] text-gray-500 pointer-events-none"></i>
                                        <input type="hidden" name="city" id="city_name">
                                    </div>
                                    <div class="flex flex-col gap-1 relative">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Barangay</label>
                                        <select id="barangay_code" required disabled class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-2 text-sm text-white transition-all outline-none appearance-none disabled:opacity-50">
                                            <option value="" disabled selected class="bg-[#13131a] text-white">Select Barangay</option>
                                        </select>
                                        <i class="ph ph-caret-down absolute right-4 top-[32px] text-gray-500 pointer-events-none"></i>
                                        <input type="hidden" name="barangay" id="barangay_name">
                                    </div>
                                </div>

                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Postal Code</label>
                                    <input type="text" id="postal_code" name="postal_code" required readonly placeholder="Auto-generated" class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-2 text-sm text-gray-400 outline-none font-mono cursor-not-allowed">
                                </div>

                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Detailed Address (Street Name, Building, House No.)</label>
                                    <input type="text" name="detailed_address" required placeholder="House/Unit Number, Street" class="w-full bg-black/40 border border-white/10 focus:border-primary focus:ring-1 focus:ring-primary focus:bg-black/60 rounded-xl px-4 py-2 text-sm text-white transition-all outline-none">
                                </div>

                                <!-- Map Section -->
                                <div class="flex flex-col gap-1 mt-1">
                                    <div class="flex items-center justify-between">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pin your location</label>
                                        <span class="text-[9px] text-gray-500">Drag map to pin</span>
                                    </div>
                                    <div id="addressMap" class="w-full h-32 rounded-xl border border-white/10 overflow-hidden bg-black/40 relative z-0">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1 items-center">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Label As:</label>
                                        <div class="flex items-center gap-3">
                                            <label class="flex items-center gap-2 cursor-pointer bg-black/40 border border-white/10 rounded-xl px-3 py-1.5 hover:border-primary transition-colors">
                                                <input type="radio" name="label" value="home" class="accent-primary w-4 h-4" checked>
                                                <span class="text-sm text-white">Home</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer bg-black/40 border border-white/10 rounded-xl px-3 py-1.5 hover:border-primary transition-colors">
                                                <input type="radio" name="label" value="work" class="accent-primary w-4 h-4">
                                                <span class="text-sm text-white">Work</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="flex flex-col justify-end">
                                        <label class="flex items-center gap-3 cursor-pointer mt-5">
                                            <div class="relative flex items-center justify-center">
                                                <input type="checkbox" name="is_default" value="1" class="peer sr-only">
                                                <div class="w-5 h-5 rounded border-2 border-gray-600 peer-checked:border-primary peer-checked:bg-primary transition-colors flex items-center justify-center">
                                                    <i class="ph-bold ph-check text-white text-[10px] opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                                                </div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-400 group-hover:text-white transition-colors">Set as default address</span>
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="mt-2 bg-gradient-to-r from-primary to-[#ff8c33] hover:from-[#ff8c33] hover:to-primary text-white w-full py-2.5 rounded-xl text-sm font-bold transition-all shadow-[0_0_15px_rgba(255,107,0,0.3)] hover:shadow-[0_0_25px_rgba(255,107,0,0.5)]">
                                    Save Address
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal JavaScript -->
                <script>
                    window.openAddAddressModal = function() {
                        const modal = document.getElementById('add-address-modal');
                        const form = document.getElementById('addAddressForm');
                        
                        modal.querySelector('h3').textContent = 'Add New Address';
                        form.action = `{{ route('ecommerce.account.addresses.store') }}`;
                        
                        const methodInput = form.querySelector('input[name="_method"]');
                        if (methodInput) methodInput.remove();
                        
                        form.reset();
                        
                        // Reset dropdowns
                        document.getElementById('province_code').innerHTML = '<option value="" disabled selected class="bg-[#13131a] text-white">Select Province</option>';
                        document.getElementById('city_code').innerHTML = '<option value="" disabled selected class="bg-[#13131a] text-white">Select City</option>';
                        document.getElementById('barangay_code').innerHTML = '<option value="" disabled selected class="bg-[#13131a] text-white">Select Barangay</option>';
                        document.getElementById('province_code').disabled = true;
                        document.getElementById('city_code').disabled = true;
                        document.getElementById('barangay_code').disabled = true;
                        
                        // Clear hidden inputs
                        document.getElementById('region_name').value = '';
                        document.getElementById('province_name').value = '';
                        document.getElementById('city_name').value = '';
                        document.getElementById('barangay_name').value = '';
                        
                        const defaultCheckbox = form.querySelector('input[name="is_default"]');
                        if (defaultCheckbox) defaultCheckbox.disabled = false;
                        
                        openModal('add-address-modal');
                        
                        // Reset map
                        if (window.map) {
                            const defaultLocation = [14.5995, 120.9842];
                            window.map.setView(defaultLocation, 13);
                            if (window.marker) window.marker.setLatLng(defaultLocation);
                            document.getElementById("latitude").value = defaultLocation[0];
                            document.getElementById("longitude").value = defaultLocation[1];
                        }
                    };

                    window.openEditAddressModal = function(address) {
                        const modal = document.getElementById('add-address-modal');
                        const form = document.getElementById('addAddressForm');
                        
                        // Update title
                        modal.querySelector('h3').textContent = 'Edit Address';
                        
                        // Update action
                        form.action = `{{ url('/account/addresses') }}/${address.id}`;
                        
                        // Add method spoofing for PUT
                        let methodInput = form.querySelector('input[name="_method"]');
                        if (!methodInput) {
                            methodInput = document.createElement('input');
                            methodInput.type = 'hidden';
                            methodInput.name = '_method';
                            methodInput.value = 'PUT';
                            form.appendChild(methodInput);
                        }
                        
                        // Set text fields
                        form.querySelector('input[name="full_name"]').value = address.full_name;
                        form.querySelector('input[name="phone_number"]').value = address.phone_number;
                        form.querySelector('input[name="postal_code"]').value = address.postal_code;
                        form.querySelector('input[name="detailed_address"]').value = address.detailed_address;
                        
                        // Set hidden location fields (preserve existing ones if dropdowns are untouched)
                        document.getElementById('region_name').value = address.region;
                        document.getElementById('province_name').value = address.province;
                        document.getElementById('city_name').value = address.city;
                        document.getElementById('barangay_name').value = address.barangay;
                        
                        // Set Label
                        const labelRadio = form.querySelector(`input[name="label"][value="${address.label}"]`);
                        if (labelRadio) labelRadio.checked = true;
                        
                        // Set default checkbox
                        const defaultCheckbox = form.querySelector('input[name="is_default"]');
                        if (defaultCheckbox) {
                            defaultCheckbox.checked = address.is_default == 1;
                            defaultCheckbox.disabled = address.is_default == 1; // can't uncheck if it's already default
                        }

                        openModal('add-address-modal');
                        
                        // Pan map to existing coordinates
                        setTimeout(() => {
                            if (window.map && address.latitude && address.longitude) {
                                const lat = parseFloat(address.latitude);
                                const lon = parseFloat(address.longitude);
                                window.map.setView([lat, lon], 15);
                                if (window.marker) window.marker.setLatLng([lat, lon]);
                            }
                        }, 250); // wait for modal animation
                    };

                    window.openModal = function(id) {
                        const modal = document.getElementById(id);
                        if (!modal) return;
                        const content = modal.querySelector('.relative');
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        setTimeout(() => {
                            content.classList.remove('scale-95', 'opacity-0');
                            content.classList.add('scale-100', 'opacity-100');
                        }, 10);
                    };

                    window.closeModal = function(id) {
                        const modal = document.getElementById(id);
                        if (!modal) return;
                        const content = modal.querySelector('.relative');
                        content.classList.remove('scale-100', 'opacity-100');
                        content.classList.add('scale-95', 'opacity-0');
                        setTimeout(() => {
                            modal.classList.remove('flex');
                            modal.classList.add('hidden');
                        }, 300);
                    };

                    window.openEditCardModal = function(id, name, expiry, actionUrl) {
                        const modal = document.getElementById('edit-card-modal');
                        if (!modal) return;
                        modal.querySelector('form').action = actionUrl;
                        modal.querySelector('#edit_cardholder_name').value = name;
                        modal.querySelector('#edit_expiry_date').value = expiry;
                        
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        setTimeout(() => {
                            const content = modal.querySelector('.relative');
                            content.classList.remove('scale-95', 'opacity-0');
                            content.classList.add('scale-100', 'opacity-100');
                        }, 10);
                    };

                    window.confirmDeleteModal = function(actionUrl) {
                        const modal = document.getElementById('delete-confirmation-modal');
                        if (!modal) return;
                        modal.querySelector('form').action = actionUrl;
                        
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        setTimeout(() => {
                            const content = modal.querySelector('.relative');
                            content.classList.remove('scale-95', 'opacity-0');
                            content.classList.add('scale-100', 'opacity-100');
                        }, 10);
                    };

                    // AJAX Form Submission for Modals
                    const setupAjaxForm = (formSelector, modalId) => {
                        const form = document.querySelector(formSelector);
                        if (!form) return;
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();
                            const submitBtn = this.querySelector('button[type="submit"]');
                            const originalHTML = submitBtn.innerHTML;
                            submitBtn.innerHTML = '<i class="ph-bold ph-spinner animate-spin"></i> Saving...';
                            submitBtn.disabled = true;

                            fetch(this.action, {
                                method: 'POST',
                                body: new FormData(this),
                                headers: { 
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(async (response) => {
                                const data = await response.json().catch(() => ({}));
                                if (!response.ok) throw data;
                                return data;
                            })
                            .then(data => {
                                form.querySelectorAll('.error-msg').forEach(el => el.classList.add('hidden'));
                                closeModal(modalId);
                                if (typeof window.showToast === 'function') {
                                    window.showToast(data.success || 'Successfully saved!');
                                }
                                // Reload pane content
                                fetch(window.location.href)
                                .then(res => res.text())
                                .then(html => {
                                    const parser = new DOMParser();
                                    const doc = parser.parseFromString(html, 'text/html');
                                    const newPane = doc.getElementById('pane-bank-cards');
                                    if (newPane) {
                                        document.getElementById('pane-bank-cards').innerHTML = newPane.innerHTML;
                                    }
                                });
                                submitBtn.innerHTML = originalHTML;
                                submitBtn.disabled = false;
                                this.reset();
                            })
                            .catch(err => {
                                form.querySelectorAll('.error-msg').forEach(el => el.classList.add('hidden'));
                                
                                if (err.errors) {
                                    // Display validation errors under inputs
                                    for (const [field, messages] of Object.entries(err.errors)) {
                                        const errorSpan = form.querySelector(`[data-error-for="${field}"]`);
                                        if (errorSpan) {
                                            errorSpan.textContent = messages[0];
                                            errorSpan.classList.remove('hidden');
                                        }
                                    }
                                } else {
                                    closeModal(modalId);
                                    const errorModal = document.getElementById('error-modal');
                                    if (errorModal) {
                                        errorModal.querySelector('p').textContent = err.error || err.message || 'Something went wrong';
                                        errorModal.classList.remove('hidden');
                                        errorModal.classList.add('flex');
                                        setTimeout(() => {
                                            const content = errorModal.querySelector('.relative');
                                            content.classList.remove('scale-95', 'opacity-0');
                                            content.classList.add('scale-100', 'opacity-100');
                                        }, 10);
                                    }
                                }
                                submitBtn.innerHTML = originalHTML;
                                submitBtn.disabled = false;
                            });
                        });
                    };

                    setupAjaxForm('#add-card-modal form', 'add-card-modal');
                    setupAjaxForm('#add-bank-modal form', 'add-bank-modal');
                    setupAjaxForm('#edit-card-modal form', 'edit-card-modal');
                    
                    // Special case for delete: we don't want a success toast
                    const deleteForm = document.querySelector('#delete-confirmation-modal form');
                    if (deleteForm) {
                        deleteForm.addEventListener('submit', function(e) {
                            e.preventDefault();
                            const submitBtn = this.querySelector('button[type="submit"]');
                            const originalHTML = submitBtn.innerHTML;
                            submitBtn.innerHTML = '<i class="ph-bold ph-spinner animate-spin"></i>';
                            submitBtn.disabled = true;

                            fetch(this.action, {
                                method: 'POST',
                                body: new FormData(this),
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(async (response) => {
                                const data = await response.json().catch(() => ({}));
                                if (!response.ok) throw new Error(data.error || 'Something went wrong');
                                return data;
                            })
                            .then(data => {
                                closeModal('delete-confirmation-modal');
                                fetch(window.location.href)
                                .then(res => res.text())
                                .then(html => {
                                    const parser = new DOMParser();
                                    const doc = parser.parseFromString(html, 'text/html');
                                    const newPane = doc.getElementById('pane-bank-cards');
                                    if (newPane) {
                                        document.getElementById('pane-bank-cards').innerHTML = newPane.innerHTML;
                                    }
                                });
                                submitBtn.innerHTML = originalHTML;
                                submitBtn.disabled = false;
                            })
                            .catch(err => {
                                closeModal('delete-confirmation-modal');
                                submitBtn.innerHTML = originalHTML;
                                submitBtn.disabled = false;
                            });
                        });
                    }
                </script>

    

    @vite(['Modules/E-Commerce/Techforge/resources/js/Common/Preloader.js', 'Modules/E-Commerce/Techforge/resources/js/Common/AmbientEffects.js'])

    <!-- Global Toast Notification -->
    <div id="toast-notification" class="fixed bottom-6 right-6 z-[200] transform translate-y-20 opacity-0 transition-all duration-300 flex items-center gap-3 bg-[#13131a] border border-primary/30 shadow-[0_0_20px_rgba(255,107,0,0.2)] rounded-xl px-5 py-4 pointer-events-none">
        <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center">
            <i class="ph-fill ph-check-circle text-primary text-xl"></i>
        </div>
        <p id="toast-message" class="text-sm font-bold text-white uppercase tracking-wider">Success!</p>
    </div>
    
    <script>
        window.showToast = function(message) {
            const toast = document.getElementById('toast-notification');
            const msgEl = document.getElementById('toast-message');
            msgEl.textContent = message;
            toast.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0');
            }, 3000);
        }
    </script>

    <!-- Load our compiled JavaScript -->
    @vite('Modules/E-Commerce/Techforge/resources/js/HomePage/Homepage.js')
<!-- Address Modal Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const API_URL = "https://psgc.cloud/api";
        const regionSelect = document.getElementById("region_code");
        const provinceSelect = document.getElementById("province_code");
        const citySelect = document.getElementById("city_code");
        const barangaySelect = document.getElementById("barangay_code");
        
        const superRegions = [
            { name: "Metro Manila", codes: ["1300000000"] },
            { name: "Mindanao", codes: ["0900000000", "1000000000", "1100000000", "1200000000", "1600000000", "1900000000"] },
            { name: "North Luzon", codes: ["0100000000", "0200000000", "0300000000", "1400000000"] },
            { name: "South Luzon", codes: ["0400000000", "1700000000", "0500000000"] },
            { name: "Visayas", codes: ["0600000000", "0700000000", "0800000000"] }
        ];

        let provinces = [];
        let cities = [];
        let barangays = [];

        function fixEncoding(str) {
            try { return decodeURIComponent(escape(str)); } catch(e) { return str; }
        }

        // Load Super Regions
        superRegions.forEach((sr, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = sr.name;
            option.className = "bg-[#13131a] text-white";
            regionSelect.appendChild(option);
        });

        regionSelect.addEventListener('change', async (e) => {
            provinceSelect.innerHTML = '<option value="" disabled selected class="bg-[#13131a] text-white">Select Province</option>';
            citySelect.innerHTML = '<option value="" disabled selected class="bg-[#13131a] text-white">Select City</option>';
            barangaySelect.innerHTML = '<option value="" disabled selected class="bg-[#13131a] text-white">Select Barangay</option>';
            provinceSelect.disabled = true;
            citySelect.disabled = true;
            barangaySelect.disabled = true;
            
            const selectedSr = superRegions[e.target.value];
            updateMapLocation(selectedSr.name + ", Philippines");

            if (selectedSr.name === "Metro Manila") {
                // NCR case: Inject "Metro Manila" as a single province option to strictly enforce flow
                const option = document.createElement('option');
                option.value = "NCR_PROV";
                option.textContent = "Metro Manila";
                option.className = "bg-[#13131a] text-white";
                provinceSelect.appendChild(option);
                provinceSelect.disabled = false;
            } else {
                try {
                    const promises = selectedSr.codes.map(code => fetch(`${API_URL}/regions/${code}/provinces`).then(res => res.json()));
                    const results = await Promise.all(promises);
                    provinces = results.flat().filter(p => p && p.code).sort((a, b) => a.name.localeCompare(b.name));
                    
                    if (provinces.length > 0) {
                        provinces.forEach(prov => {
                            const option = document.createElement('option');
                            option.value = prov.code;
                            option.textContent = fixEncoding(prov.name);
                            option.className = "bg-[#13131a] text-white";
                            provinceSelect.appendChild(option);
                        });
                        provinceSelect.disabled = false;
                    }
                } catch (err) {
                    console.error("Error fetching provinces:", err);
                }
            }
        });

        provinceSelect.addEventListener('change', (e) => {
            if (e.target.value === "NCR_PROV") {
                fetchCities(`${API_URL}/regions/1300000000/cities-municipalities`, "Metro Manila, Philippines");
            } else {
                fetchCities(`${API_URL}/provinces/${e.target.value}/cities-municipalities`, provinceSelect.options[provinceSelect.selectedIndex].text + ", Philippines");
            }
        });

        function fetchCities(url, locationQuery) {
            citySelect.innerHTML = '<option value="" disabled selected class="bg-[#13131a] text-white">Select City</option>';
            barangaySelect.innerHTML = '<option value="" disabled selected class="bg-[#13131a] text-white">Select Barangay</option>';
            citySelect.disabled = true;
            barangaySelect.disabled = true;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    cities = data.sort((a, b) => a.name.localeCompare(b.name));
                    cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.code;
                        option.textContent = fixEncoding(city.name);
                        option.className = "bg-[#13131a] text-white";
                        citySelect.appendChild(option);
                    });
                    citySelect.disabled = false;
                    updateMapLocation(locationQuery);
                });
        }

        citySelect.addEventListener('change', (e) => {
            barangaySelect.innerHTML = '<option value="" disabled selected class="bg-[#13131a] text-white">Select Barangay</option>';
            barangaySelect.disabled = true;

            fetch(`${API_URL}/cities-municipalities/${e.target.value}/barangays`)
                .then(res => res.json())
                .then(data => {
                    barangays = data.sort((a, b) => a.name.localeCompare(b.name));
                    barangays.forEach(brgy => {
                        const option = document.createElement('option');
                        option.value = brgy.code;
                        option.textContent = fixEncoding(brgy.name);
                        option.className = "bg-[#13131a] text-white";
                        barangaySelect.appendChild(option);
                    });
                    barangaySelect.disabled = false;
                    
                    // Generate or fetch Postal Code based on city
                    const postalCodeInput = document.getElementById("postal_code");
                    const selectedCityObj = cities.find(c => c.code === e.target.value);
                    if (selectedCityObj && selectedCityObj.zip_code) {
                        postalCodeInput.value = selectedCityObj.zip_code;
                    } else {
                        postalCodeInput.value = generateMockPostalCode(citySelect.options[citySelect.selectedIndex].text);
                    }

                    updateMapLocation(citySelect.options[citySelect.selectedIndex].text + ", Philippines");
                });
        });

        function generateMockPostalCode(cityName) {
            // Very simple hash to generate a plausible PH postal code (4 digits)
            let hash = 0;
            for (let i = 0; i < cityName.length; i++) {
                hash = cityName.charCodeAt(i) + ((hash << 5) - hash);
            }
            let code = Math.abs(hash) % 9000 + 1000;
            return code.toString();
        }

        // --- Leaflet Maps Logic (Free Alternative) ---
        let map;
        let marker;

        // Load Leaflet CSS and JS dynamically
        if (!document.getElementById('leaflet-css')) {
            const css = document.createElement('link');
            css.id = 'leaflet-css';
            css.rel = 'stylesheet';
            css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(css);
        }

        if (!document.getElementById('leaflet-script')) {
            const script = document.createElement('script');
            script.id = 'leaflet-script';
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.onload = initMap;
            document.head.appendChild(script);
        } else if (window.L) {
            initMap();
        }

        function initMap() {
            if (map) return;
            const defaultLocation = [14.5995, 120.9842]; // Manila
            
            map = L.map('addressMap').setView(defaultLocation, 13);
            
            // Use CartoDB Dark Matter tile layer for dark theme
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                maxZoom: 19
            }).addTo(map);

            marker = L.marker(defaultLocation, { draggable: true }).addTo(map);

            document.getElementById("latitude").value = defaultLocation[0];
            document.getElementById("longitude").value = defaultLocation[1];

            marker.on('dragend', function(event) {
                const position = marker.getLatLng();
                document.getElementById("latitude").value = position.lat;
                document.getElementById("longitude").value = position.lng;
            });

            map.on('click', function(event) {
                marker.setLatLng(event.latlng);
                document.getElementById("latitude").value = event.latlng.lat;
                document.getElementById("longitude").value = event.latlng.lng;
            });
        }

        function updateMapLocation(addressQuery) {
            if (!map) return;
            // Nominatim Geocoding API (Free OpenStreetMap Geocoding)
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(addressQuery)}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lon = parseFloat(data[0].lon);
                        map.setView([lat, lon], 13);
                        marker.setLatLng([lat, lon]);
                        document.getElementById("latitude").value = lat;
                        document.getElementById("longitude").value = lon;
                    }
                })
                .catch(err => console.error("Geocoding error:", err));
        }
        
        // Re-initialize map when modal is opened to fix sizing issues
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.target.id === 'add-address-modal' && !mutation.target.classList.contains('hidden')) {
                    if (map) {
                        setTimeout(() => {
                            map.invalidateSize();
                        }, 200);
                    }
                }
            });
        });
        
        const modalEl = document.getElementById('add-address-modal');
        if (modalEl) observer.observe(modalEl, { attributes: true, attributeFilter: ['class'] });

        // Handle form submit via AJAX
        const addAddressForm = document.getElementById("addAddressForm");
        if (addAddressForm) {
            addAddressForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (regionSelect.selectedIndex > 0) document.getElementById('region_name').value = regionSelect.options[regionSelect.selectedIndex].text;
                if (provinceSelect.selectedIndex > 0) document.getElementById('province_name').value = provinceSelect.options[provinceSelect.selectedIndex].text;
                if (citySelect.selectedIndex > 0) document.getElementById('city_name').value = citySelect.options[citySelect.selectedIndex].text;
                if (barangaySelect.selectedIndex > 0) document.getElementById('barangay_name').value = barangaySelect.options[barangaySelect.selectedIndex].text;

                const submitBtn = addAddressForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="ph-bold ph-spinner animate-spin mr-2"></i>Saving...';
                submitBtn.disabled = true;

                const formData = new FormData(addAddressForm);

                fetch(addAddressForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json().then(data => ({ status: res.status, body: data })))
                .then(({ status, body }) => {
                    if (status === 200 && (body.success || !body.error)) {
                        // Success! Let's refresh the addresses pane contents without reloading the page
                        fetch(window.location.href)
                            .then(res => res.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newAddressesHTML = doc.querySelector('#pane-addresses').innerHTML;
                                document.querySelector('#pane-addresses').innerHTML = newAddressesHTML;
                                
                                addAddressForm.reset();
                                closeModal('add-address-modal');
                            });
                    } else {
                        // Handle Laravel Validation errors dynamically if present
                        let errorMsg = body.error || body.message || 'Failed to save address.';
                        if (body.errors) {
                            errorMsg = Object.values(body.errors).flat().join('\n');
                        }
                        alert(errorMsg);
                    }
                })
                .catch(err => {
                    console.error("Save error:", err);
                    alert("An error occurred while saving the address. Please try again.");
                })
                .finally(() => {
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                });
            });
        }
    });
</script>
</body>
</html>
