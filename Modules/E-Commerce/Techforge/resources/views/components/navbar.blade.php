    @php
        $storefrontCompany = request()->attributes->get('ecommerce_company');
        $storefrontName = $storefrontCompany?->company_name ?: 'Nexora Store';
    @endphp

    <!-- Search Overlay -->
    <div id="search-overlay" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[75] opacity-0 pointer-events-none transition-all duration-300"></div>

    <!-- Nav Overlay (for Peripherals Store dropdown) -->
    <div id="nav-overlay" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[65] opacity-0 pointer-events-none transition-all duration-300"></div>

    <!-- Navigation -->
    <nav class="fixed w-[calc(100%-2rem)] sm:w-[calc(100%-3rem)] lg:w-[calc(100%-4rem)] max-w-7xl left-1/2 -translate-x-1/2 top-4 z-[80] px-4 sm:px-6 py-3 flex items-center justify-between gap-4 sm:gap-6 transition-all duration-300">
        <!-- Background for Nav to prevent backdrop-filter nesting bug -->
        <div class="absolute inset-0 bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl pointer-events-none shadow-2xl"></div>

        <!-- Logo & Name -->
        <a href="{{ url('/') }}" class="flex items-center gap-3 shrink-0 relative z-30">
            <div class="bg-gradient-to-br from-primary to-orange-400 w-10 h-10 rounded-xl flex items-center justify-center shadow-[0_0_15px_rgba(255,107,0,0.4)]">
                <img src="{{ asset('ecommerce/Nexora_Logo.png') }}" alt="{{ $storefrontName }} logo" class="h-6 w-auto object-contain">
            </div>
            <span class="hidden md:block text-xl font-bold tracking-wide text-white">{{ $storefrontName }}</span>
        </a>



        <!-- Search Bar (Automatically Enlarged) -->
        <form id="search-container" action="{{ route('ecommerce.search') }}" method="GET" class="flex-1 w-full relative z-50">
            <div id="search-wrapper" class="relative flex items-center w-full h-11 bg-neutral-900 border border-white/10 hover:border-white/20 hover:bg-white/5 transition-all duration-300 rounded-2xl group">
                <input type="text" name="q" id="search-input" placeholder="What are we searching?" class="w-full h-full bg-transparent outline-none pl-5 pr-20 text-sm text-white placeholder-gray-400 font-light rounded-2xl relative z-10" autocomplete="off" value="{{ request('q') }}">
                
                <!-- Clear Button -->
                <button type="button" id="search-clear" class="absolute right-12 w-7 h-7 flex items-center justify-center text-gray-400 hover:text-white transition-all opacity-0 pointer-events-none z-20">
                    <i class="ph ph-x text-sm"></i>
                </button>

                <button type="submit" class="absolute right-1 w-9 h-9 flex items-center justify-center bg-primary hover:bg-primary-hover text-white rounded-xl transition-colors shadow-[0_0_10px_rgba(255,107,0,0.3)] z-20">
                    <i class="ph ph-magnifying-glass text-lg"></i>
                </button>
            </div>
            
            <!-- Search Dropdown -->
            <div id="search-dropdown" class="bg-[#1a1a1a]/90 backdrop-blur-2xl border border-white/10 absolute top-[calc(100%+0.5rem)] left-0 w-full rounded-2xl overflow-hidden shadow-2xl py-4 opacity-0 pointer-events-none transition-all duration-300 transform -translate-y-2 origin-top">
                <div class="px-5 mb-2">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Popular Searches</span>
                </div>
                <ul class="text-sm text-gray-300 flex flex-col">
                    <li><a href="{{ route('ecommerce.search', ['q' => 'RTX 4090']) }}" class="flex items-center gap-3 px-5 py-2.5 hover:bg-white/5 hover:text-primary transition-colors"><i class="ph ph-magnifying-glass text-gray-500"></i> RTX 4090</a></li>
                    <li><a href="{{ route('ecommerce.search', ['q' => 'Ryzen 7 7800X3D']) }}" class="flex items-center gap-3 px-5 py-2.5 hover:bg-white/5 hover:text-primary transition-colors"><i class="ph ph-magnifying-glass text-gray-500"></i> Ryzen 7 7800X3D</a></li>
                    <li><a href="{{ route('ecommerce.search', ['q' => 'Prebuilt Gaming PC']) }}" class="flex items-center gap-3 px-5 py-2.5 hover:bg-white/5 hover:text-primary transition-colors"><i class="ph ph-magnifying-glass text-gray-500"></i> Prebuilt Gaming PC</a></li>
                    <li><a href="{{ route('ecommerce.search', ['q' => '32GB DDR5 RAM']) }}" class="flex items-center gap-3 px-5 py-2.5 hover:bg-white/5 hover:text-primary transition-colors"><i class="ph ph-magnifying-glass text-gray-500"></i> 32GB DDR5 RAM</a></li>
                </ul>
            </div>
        </form>

        <!-- Actions -->
        <div class="flex items-center gap-4 shrink-0">
            
            <!-- Sign In -->
            @auth('ecommerce')
            <div class="hidden lg:flex items-center gap-4 relative group/user py-2">
                <div class="flex items-center gap-2 cursor-pointer">
                    <i class="ph ph-user text-xl text-primary transition-colors"></i>
                    <div class="flex flex-col text-left">
                        <span class="text-[10px] text-gray-400 leading-tight">Welcome</span>
                        <span class="text-sm font-bold text-white leading-tight">{{ Auth::guard('ecommerce')->user()->name }}</span>
                    </div>
                </div>
                
                <!-- Dropdown Menu -->
                <div class="opacity-0 pointer-events-none scale-95 group-hover/user:opacity-100 group-hover/user:pointer-events-auto group-hover/user:scale-100 transition-all duration-300 origin-top-right absolute top-full right-0 mt-0 w-56 bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl py-2 z-50">
                    <div class="px-4 py-3 border-b border-white/10 mb-2 bg-white/5 mx-2 rounded-lg">
                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-0.5">FORGE Points</p>
                        <div class="flex items-end gap-2">
                            <p class="text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-primary to-[#ff8c33]">0</p>
                            <p class="text-[10px] font-normal text-gray-500 mb-1 pb-0.5">(For now)</p>
                        </div>
                    </div>
                    <a href="{{ route('ecommerce.account.profile') }}" class="flex items-center gap-3 px-5 py-2.5 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                        <i class="ph ph-user-circle text-lg text-gray-400"></i> My Account
                    </a>
                    <a href="#" class="flex items-center gap-3 px-5 py-2.5 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                        <i class="ph ph-receipt text-lg text-gray-400"></i> Order History
                    </a>
                    
                    <form action="{{ route('ecommerce.logout') }}" method="POST" class="w-full mt-2 border-t border-white/10 pt-2">
                        @csrf
                        <button type="submit" class="flex items-center gap-3 w-full text-left px-5 py-2.5 text-sm font-bold text-red-500 hover:text-red-400 hover:bg-red-500/10 transition-colors">
                            <i class="ph ph-sign-out text-lg"></i> Sign Out
                        </button>
                    </form>
                </div>
            </div>
            @else
            <div class="hidden lg:flex relative group/guest py-2">
                <a href="{{ route('ecommerce.login') }}" class="flex items-center gap-2 cursor-pointer">
                    <i class="ph ph-user text-xl text-gray-400 group-hover/guest:text-primary transition-colors"></i>
                    <div class="flex flex-col text-left">
                        <span class="text-[10px] text-gray-400 leading-tight">Welcome</span>
                        <span class="text-sm font-bold text-white group-hover/guest:text-primary transition-colors leading-tight">Sign In / Register</span>
                    </div>
                </a>
                
                <!-- Unauthenticated Dropdown -->
                <div class="opacity-0 pointer-events-none scale-95 group-hover/guest:opacity-100 group-hover/guest:pointer-events-auto group-hover/guest:scale-100 transition-all duration-300 origin-top-right absolute top-full right-0 mt-0 w-64 bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl p-4 z-50">
                    <h4 class="text-sm font-bold text-white mb-2">Join {{ $storefrontName }}</h4>
                    <p class="text-[11px] text-gray-400 mb-4 leading-snug">Earn Forge Points, track orders, and checkout faster.</p>
                    <a href="{{ route('ecommerce.login') }}" class="block w-full bg-gradient-to-r from-primary to-orange-400 hover:from-primary hover:to-orange-500 text-white text-center py-2.5 rounded-xl font-bold text-sm transition-colors mb-2 shadow-[0_0_15px_rgba(255,107,0,0.3)]">Sign In</a>
                    <a href="{{ route('ecommerce.login') }}" class="block w-full bg-white/5 hover:bg-white/10 border border-white/10 text-white text-center py-2.5 rounded-xl font-bold text-sm transition-colors">Create Account</a>
                </div>
            </div>
            @endauth

            <!-- Notification Container -->
            <div class="relative z-30 shrink-0 group">
                <!-- Notification Button -->
                <a href="{{ route('ecommerce.notifications') }}" class="w-11 h-11 flex items-center justify-center rounded-2xl border border-white/10 hover:border-white/20 hover:bg-white/5 transition-all text-gray-300 hover:text-white relative shrink-0">
                    <i class="ph ph-bell text-xl"></i>
                    <span class="absolute top-[10px] right-[10px] w-2 h-2 bg-primary rounded-full shadow-[0_0_8px_rgba(255,107,0,0.8)]"></span>
                </a>

                <!-- Notification Dropdown -->
                <div class="bg-[#1a1a1a]/90 backdrop-blur-2xl border border-white/10 absolute top-[calc(100%+0.5rem)] right-0 w-80 sm:w-96 rounded-2xl overflow-hidden shadow-2xl p-5 opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition-all duration-300 transform group-hover:translate-y-0 -translate-y-2 origin-top">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-white">Notifications</h3>
                        <span class="bg-primary/20 text-primary text-[10px] font-bold px-2 py-1 rounded-md">1 New</span>
                    </div>
                    
                    <div class="flex flex-col gap-3 mb-4">
                        <!-- Notification Item -->
                        <a href="{{ route('ecommerce.login') }}" class="flex items-start gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors group/item border border-transparent hover:border-white/5">
                            <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center shrink-0">
                                <i class="ph-fill ph-ticket text-xl text-primary"></i>
                            </div>
                            <div class="flex-1 min-w-0 pt-0.5">
                                <h4 class="text-sm font-bold text-white mb-1 group-hover/item:text-primary transition-colors">Special Offer!</h4>
                                <p class="text-xs text-gray-400 leading-relaxed">Sign up for an account now to receive a 10% discount voucher on your first order.</p>
                                <span class="text-[10px] text-gray-500 mt-2 block">Just now</span>
                            </div>
                        </a>
                    </div>
                    
                    <div class="flex justify-center pt-3 border-t border-white/10 mt-2">
                        <a href="{{ route('ecommerce.notifications') }}" class="text-xs font-bold text-gray-400 hover:text-primary transition-colors">
                            View All Notifications
                        </a>
                    </div>
                </div>
            </div>

            <!-- Cart Container -->
            <div id="cart-container" class="relative z-30 shrink-0 group/cart py-2">
                <a href="#" id="cart-btn" onclick="event.preventDefault(); toggleMiniCart()" class="flex items-center gap-2 w-auto h-11 px-3 sm:px-4 rounded-2xl border border-white/10 hover:border-white/20 hover:bg-white/5 transition-all text-gray-300 hover:text-white relative">
                    <div class="relative">
                        <i class="ph ph-shopping-cart text-xl"></i>
                        <span id="cart-badge" class="hidden absolute -top-1 -right-1 w-3.5 h-3.5 flex items-center justify-center text-[8px] font-bold bg-primary text-white rounded-full">0</span>
                    </div>
                    <div class="hidden sm:flex flex-col text-left ml-1">
                        <span class="text-[10px] text-gray-400 leading-tight">Returns</span>
                        <span class="text-sm font-bold text-white leading-tight">& Cart</span>
                    </div>
                </a>

                            </div>
            
        </div>
    </nav>

    <!-- Secondary Navigation -->
    <div id="secondary-nav" class="fixed w-[calc(100%-2rem)] sm:w-[calc(100%-3rem)] lg:w-[calc(100%-4rem)] max-w-7xl left-1/2 -translate-x-1/2 top-[112px] z-[70] hidden md:flex items-center px-6 py-2.5 bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl shadow-xl transition-all duration-300">
        <div class="flex items-center gap-8 lg:gap-12 text-[10px] font-bold tracking-widest uppercase">
            <div class="relative" id="gaming-pcs-container">
                <a href="#" id="gaming-pcs-btn" class="text-gray-200 hover:text-primary transition-colors flex items-center gap-1.5 py-2 cursor-pointer">
                    Gaming PCs <i id="gaming-pcs-icon" class="ph ph-caret-down text-[10px] text-gray-500 transition-colors"></i>
                </a>

                <!-- Dropdown Mega Menu -->
                <div id="gaming-pcs-dropdown" class="absolute top-[calc(100%+1rem)] -left-2 w-[400px] bg-[#1a1a1a]/90 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.8)] opacity-0 pointer-events-none transition-all duration-300 transform translate-y-2 overflow-hidden z-[75]">
                    <div class="grid grid-cols-1 gap-8 pt-8 pb-6 px-8">
                        <!-- Column 1: Shop -->
                        <div>
                            <h4 class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mb-6">Shop</h4>
                            <div class="flex flex-col gap-4">
                                <a href="{{ url('/prebuilt-pcs') }}" class="block text-gray-300 hover:text-primary transition-colors">
                                    <span class="font-bold text-sm tracking-normal capitalize">Pre-Built PCs</span>
                                </a>
                                <a href="{{ url('/pc-configurator') }}" class="block text-gray-300 hover:text-primary transition-colors">
                                    <span class="font-bold text-sm tracking-normal capitalize">PC Configurator</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Banner -->
                    <div class="bg-black/60 border-t border-white/10 p-6 relative overflow-hidden group/banner">
                        <div class="relative z-10 w-[70%]">
                            <p class="text-[9px] text-gray-400 uppercase tracking-widest mb-1">Build from the ground up.</p>
                            <h4 class="text-xl font-bold text-white mb-2">PC FORGE</h4>
                            <p class="text-[11px] text-gray-400 tracking-normal leading-relaxed mb-4 normal-case">Use our exclusive PC Forge tool to build your ultimate rig entirely from scratch, part by part.</p>
                            <a href="{{ route('ecommerce.build-pc') }}" class="inline-block px-5 py-2 border border-white/20 text-xs font-bold text-white rounded-full hover:bg-white hover:text-black transition-all">Launch PC Forge</a>
                        </div>
                        
                        <!-- Decoration -->
                        <div class="absolute -right-4 bottom-0 w-32 h-full flex items-center justify-center opacity-50 group-hover/banner:opacity-100 group-hover/banner:scale-110 transition-all duration-500">
                            <i class="ph-fill ph-cpu text-[100px] text-primary/30 blur-[2px]"></i>
                            <i class="ph-fill ph-hammer text-[70px] text-primary absolute drop-shadow-[0_0_10px_rgba(255,107,0,0.5)]"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative" id="gaming-laptops-container">
                <a href="#" id="gaming-laptops-btn" class="text-gray-200 hover:text-primary transition-colors flex items-center gap-1.5 py-2 cursor-pointer">
                    Gaming Laptops <i id="gaming-laptops-icon" class="ph ph-caret-down text-[10px] text-gray-500 transition-colors"></i>
                </a>

                <!-- Dropdown Mega Menu -->
                <div id="gaming-laptops-dropdown" class="absolute top-[calc(100%+1rem)] -left-2 w-[400px] bg-[#1a1a1a]/90 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.8)] opacity-0 pointer-events-none transition-all duration-300 transform translate-y-2 overflow-hidden z-[75]">
                    <div class="grid grid-cols-1 gap-8 pt-8 pb-6 px-8">
                        <!-- Column 1: Shop -->
                        <div>
                            <h4 class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mb-6">Shop</h4>
                            <div class="flex flex-col gap-4">
                                <a href="{{ url('/gaming-laptops') }}" class="block text-gray-300 hover:text-primary transition-colors">
                                    <span class="font-bold text-sm tracking-normal capitalize">All Gaming Laptops</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Banner -->
                    <div class="bg-black/60 border-t border-white/10 p-6 relative overflow-hidden group/banner">
                        <div class="relative z-10 w-[70%]">
                            <p class="text-[9px] text-gray-400 uppercase tracking-widest mb-1">Unleash true mobility.</p>
                            <h4 class="text-xl font-bold text-white mb-2">POWER ON THE GO</h4>
                            <p class="text-[11px] text-gray-400 tracking-normal leading-relaxed mb-4 normal-case">Experience desktop-class performance wherever you are with our RTX 40-series gaming laptops.</p>
                            <a href="{{ url('/gaming-laptops') }}" class="inline-block px-5 py-2 border border-white/20 text-xs font-bold text-white rounded-full hover:bg-white hover:text-black transition-all">Shop Laptops</a>
                        </div>
                        
                        <!-- Decoration -->
                        <div class="absolute -right-4 bottom-0 w-32 h-full flex items-center justify-center opacity-50 group-hover/banner:opacity-100 group-hover/banner:scale-110 transition-all duration-500">
                            <i class="ph-fill ph-laptop text-[100px] text-primary/30 blur-[2px]"></i>
                            <i class="ph-fill ph-lightning text-[70px] text-primary absolute drop-shadow-[0_0_10px_rgba(255,107,0,0.5)]"></i>
                        </div>
                    </div>
                </div>
            </div>
            <a href="{{ url('/build-pc') }}" class="text-gray-200 hover:text-primary transition-colors py-2">PC Forge</a>
            <div class="relative" id="forge-store-container">
                <a href="#" id="forge-store-btn" class="text-gray-200 hover:text-primary transition-colors flex items-center gap-1.5 py-2 cursor-pointer">
                    Forge Store <i id="forge-store-icon" class="ph ph-caret-down text-[10px] text-gray-500 transition-colors"></i>
                </a>

                <!-- Dropdown Mega Menu -->
                <div id="forge-store-dropdown" class="absolute top-[calc(100%+1rem)] -left-2 w-[800px] bg-[#1a1a1a]/90 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.8)] opacity-0 pointer-events-none transition-all duration-300 transform translate-y-2 overflow-hidden z-[75]">
                    <div class="grid grid-cols-3 gap-8 pt-8 pb-8 px-8">
                        <!-- Column 1: Store -->
                        <div>
                            <h4 class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mb-6 h-8">Store</h4>
                            <div class="flex flex-col gap-4">
                                <a href="{{ url('/forge-store') }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">Explore Forge Store</span></a>
                            </div>
                        </div>

                        <!-- Column 2: Gaming Accessories and Monitors -->
                        <div>
                            <h4 class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mb-6 h-8">Gaming Accessories and Monitors</h4>
                            <div class="flex flex-col gap-4">
                                <a href="{{ route('ecommerce.store.monitors') }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">Monitors</span></a>
                                <a href="{{ route('ecommerce.store.accessories', ['category' => 'keyboards']) }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">Keyboards</span></a>
                                <a href="{{ route('ecommerce.store.accessories', ['category' => 'keyboard-accessories']) }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">Keyboard Accessories</span></a>
                                <a href="{{ route('ecommerce.store.accessories', ['category' => 'headsets']) }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">Headsets</span></a>
                                <a href="{{ route('ecommerce.store.accessories', ['category' => 'mice']) }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">Mice</span></a>
                                <a href="{{ route('ecommerce.store.accessories', ['category' => 'mouse-pads']) }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">Mouse Pad</span></a>
                                <a href="{{ route('ecommerce.store.accessories', ['category' => 'speaker-systems']) }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">Speaker Systems</span></a>
                            </div>
                        </div>

                        <!-- Column 3: PC Parts -->
                        <div>
                            <h4 class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mb-6 h-8">PC Parts</h4>
                            <div class="flex flex-col gap-4">
                                <a href="{{ route('ecommerce.store.pc-parts', ['category' => 'cases']) }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">Cases</span></a>
                                <a href="{{ route('ecommerce.store.pc-parts', ['category' => 'storages']) }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">Storage</span></a>
                                <a href="{{ route('ecommerce.store.pc-parts', ['category' => 'video-cards']) }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">Video Cards</span></a>
                                <a href="{{ route('ecommerce.store.pc-parts', ['category' => 'coolers']) }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">PC Cooling</span></a>
                                <a href="{{ route('ecommerce.store.pc-parts', ['category' => 'power-supplies']) }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">Power Supplies</span></a>
                                <a href="{{ route('ecommerce.store.pc-parts', ['category' => 'motherboards']) }}" class="block text-gray-300 hover:text-primary transition-colors"><span class="font-bold text-sm tracking-normal capitalize">Motherboards</span></a>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    @vite('Modules/E-Commerce/Techforge/resources/js/Common/Navbar.js')
    <!-- Mini Cart Drawer -->
    <div id="mini-cart-overlay" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[90] opacity-0 pointer-events-none transition-all duration-300" onclick="toggleMiniCart()"></div>

    <div id="mini-cart-drawer" class="fixed top-0 right-0 w-full sm:w-[400px] h-full bg-[#050505] border-l border-white/10 shadow-2xl z-[100] transform translate-x-full transition-transform duration-500 flex flex-col">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between bg-white/5">
            <h2 class="text-lg font-bold text-white tracking-widest uppercase flex items-center gap-3">
                <i class="ph-bold ph-shopping-cart text-primary"></i> Your Cart
            </h2>
            <button onclick="toggleMiniCart()" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white flex items-center justify-center transition-all">
                <i class="ph-bold ph-x"></i>
            </button>
        </div>

        <!-- Cart Items -->
        <div id="mini-cart-items" class="flex-1 overflow-y-auto p-6 space-y-4">
            <div class="flex flex-col items-center justify-center h-full text-center opacity-50">
                <i class="ph-light ph-shopping-cart text-5xl mb-3 text-gray-400"></i>
                <p class="text-sm font-bold text-white">Your cart is empty.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-white/10 bg-white/5">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-bold text-gray-400 uppercase tracking-widest">Subtotal</span>
                <span id="mini-cart-subtotal" class="text-xl font-black text-white">₱0.00</span>
            </div>
            <a href="{{ route('ecommerce.cart') }}" class="block w-full bg-primary hover:bg-white hover:text-black text-white text-center py-4 rounded-xl font-black uppercase tracking-widest transition-all duration-300 shadow-[0_0_20px_rgba(255,107,0,0.3)]">
                Checkout Now
            </a>
        </div>
    </div>

    <script>
        window.csrfToken = "{{ csrf_token() }}";
        
        function toggleMiniCart() {
            const drawer = document.getElementById('mini-cart-drawer');
            const overlay = document.getElementById('mini-cart-overlay');
            if (drawer.classList.contains('translate-x-full')) {
                drawer.classList.remove('translate-x-full');
                overlay.classList.remove('opacity-0', 'pointer-events-none');
                overlay.classList.add('opacity-100', 'pointer-events-auto');
            } else {
                drawer.classList.add('translate-x-full');
                overlay.classList.add('opacity-0', 'pointer-events-none');
                overlay.classList.remove('opacity-100', 'pointer-events-auto');
            }
        }

        function updateMiniCartUI(cartCount, items) {
            const badge = document.getElementById('cart-badge');
            if (cartCount > 0) {
                badge.textContent = cartCount;
                badge.classList.remove('hidden');
                badge.classList.add('flex');
            } else {
                badge.classList.add('hidden');
                badge.classList.remove('flex');
            }

            const itemsContainer = document.getElementById('mini-cart-items');
            const subtotalEl = document.getElementById('mini-cart-subtotal');
            
            if (!items || items.length === 0) {
                itemsContainer.innerHTML = `<div class="flex flex-col items-center justify-center h-full text-center opacity-50">
                    <i class="ph-light ph-shopping-cart text-5xl mb-3 text-gray-400"></i>
                    <p class="text-sm font-bold text-white">Your cart is empty.</p>
                </div>`;
                subtotalEl.textContent = '₱0.00';
                return;
            }

            let html = '';
            let subtotal = 0;
            items.forEach(item => {
                subtotal += item.price * item.quantity;
                html += `
                <div class="flex items-center gap-4 bg-black/40 border border-white/5 rounded-xl p-3">
                    <div class="w-16 h-16 rounded-lg bg-white/5 flex items-center justify-center p-2 shrink-0">
                        ${item.image_url ? `<img src="${item.image_url}" class="max-w-full max-h-full object-contain">` : `<i class="ph ph-package text-2xl text-gray-500"></i>`}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-bold text-white truncate mb-1">${item.name}</h4>
                        <div class="text-xs text-gray-400 mb-1">Qty: ${item.quantity}</div>
                        <div class="text-sm font-bold text-primary">₱${parseFloat(item.price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    </div>
                </div>`;
            });

            itemsContainer.innerHTML = html;
            subtotalEl.textContent = '₱' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        window.addToCart = function(productId, name, price, imageUrl, quantity = 1, productType = 'generic', configuration = null, btn = null) {
            if (typeof price === 'string') {
                price = parseFloat(price.replace(/,/g, ''));
            }

            let originalContent = '';
            if (btn) {
                originalContent = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="ph ph-spinner animate-spin text-lg"></i>';
            }

            fetch('/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    name: name,
                    price: price,
                    quantity: quantity,
                    image_url: imageUrl,
                    product_type: productType,
                    configuration: configuration
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (btn) {
                        btn.innerHTML = '<i class="ph-bold ph-check text-lg"></i>';
                        btn.classList.add('!bg-green-500', '!border-green-500', '!text-white');
                        setTimeout(() => {
                            btn.innerHTML = originalContent;
                            btn.disabled = false;
                            btn.classList.remove('!bg-green-500', '!border-green-500', '!text-white');
                        }, 2000);
                    }
                    
                    updateMiniCartUI(data.cart_count, data.cart_items);
                    const drawer = document.getElementById('mini-cart-drawer');
                    if (drawer && drawer.classList.contains('translate-x-full')) {
                        toggleMiniCart();
                    }
                } else if (btn) {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error('Error adding to cart:', err);
                if (btn) {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            });
        }

        // Fetch initial cart count on load
        document.addEventListener('DOMContentLoaded', () => {
            fetch('/cart/count', {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                updateMiniCartUI(data.cart_count, data.cart_items);
            });
        });
    </script>



