<nav class="topnav">
    <div class="logo">
        <img src="{{ asset('images/procurement-banner.png') }}" alt="Nexora ERP">
    </div>
    <div class="divider-v"></div>
    <strong style="color:#fff;">Procurement</strong>
    <div class="nav-right" style="margin-left:auto; display:flex; align-items:center; gap:12px; color:#fff;">
        <span>{{ session('employee_name', 'Employee') }}</span>
        <form method="POST" action="{{ route('procurement.logout') }}">
            @csrf
            <button type="submit" style="background:transparent;border:1px solid #7BBEF0;border-radius:6px;color:#fff;padding:6px 10px;cursor:pointer;">Logout</button>
        </form>
    </div>
</nav>