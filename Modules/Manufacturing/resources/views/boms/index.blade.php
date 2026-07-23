<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manufacturing · Bill of Materials</title>
    <style>
        body{margin:0;background:#f4f7fb;color:#132b52;font-family:Inter,Arial,sans-serif}.top{background:#132b52;color:#fff;padding:18px 7%;display:flex;justify-content:space-between;align-items:center}.top a{color:#fff;text-decoration:none}.wrap{max-width:1200px;margin:30px auto;padding:0 20px}.grid{display:grid;grid-template-columns:380px 1fr;gap:22px}.card{background:#fff;border:1px solid #d9e2ef;border-radius:12px;padding:22px;box-shadow:0 4px 14px #132b5212}h1,h2{margin-top:0}label{display:block;font-size:13px;font-weight:700;margin:13px 0 6px}input,textarea,select{box-sizing:border-box;width:100%;padding:10px;border:1px solid #bdcadb;border-radius:7px}button{background:#1d4e89;color:#fff;border:0;border-radius:7px;padding:10px 14px;font-weight:700;cursor:pointer}.secondary{background:#e8eff8;color:#132b52}.line{display:grid;grid-template-columns:1fr 90px 34px;gap:8px;margin-top:8px}.bom{border-top:1px solid #e5ebf3;padding:16px 0}.muted{color:#64748b;font-size:13px}.success{padding:12px;background:#dcfce7;color:#166534;border-radius:7px;margin-bottom:14px}.error{color:#b91c1c;font-size:13px}@media(max-width:800px){.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<header class="top"><strong>Nexora · Manufacturing</strong><nav><a href="{{ route('manufacturing.dashboard') }}">Dashboard</a> &nbsp; <a href="{{ url('/ecommerce-admin') }}">E-commerce Admin</a></nav></header>
<main class="wrap">
    <h1>Bill of Materials</h1><p class="muted">A product can be listed in E-commerce only after an active BOM exists here.</p>
    @if(session('success'))<div class="success">{{ session('success') }}</div>@endif
    <div class="grid">
        <section class="card"><h2>Create BOM</h2>
            <form method="post" action="{{ route('manufacturing.boms.store') }}">@csrf
                <label>Product SKU</label><input name="sku" value="{{ old('sku') }}" required>
                <label>Product name</label><input name="name" value="{{ old('name') }}" required>
                <label>Description</label><textarea name="description">{{ old('description') }}</textarea>
                <label>Inventory components</label><div id="components"></div>
                <button type="button" class="secondary" onclick="addComponent()">+ Add component</button>
                @error('items')<p class="error">{{ $message }}</p>@enderror
                <p><button type="submit">Create active BOM</button></p>
            </form>
        </section>
        <section class="card"><h2>Active BOMs</h2>
            @forelse($boms as $bom)<article class="bom"><div style="display:flex;justify-content:space-between;gap:12px"><div><strong>{{ $bom->name }}</strong> <span class="muted">{{ $bom->sku }}</span><p class="muted">{{ $bom->description }}</p></div><form method="post" action="{{ route('manufacturing.boms.destroy', $bom) }}">@csrf @method('delete')<button class="secondary">Remove</button></form></div><ul>@foreach($bom->items as $item)<li>{{ $item->item_name }} — {{ $item->quantity_required }}</li>@endforeach</ul></article>@empty<p class="muted">No BOMs yet. Create one from inventory components.</p>@endforelse
        </section>
    </div>
</main>
<script>
const inventory = @json($inventoryItems->map(fn($item)=>['id'=>$item->id,'label'=>trim($item->sku.' · '.$item->name)])->values());
let componentIndex = 0;
function addComponent(){const i=componentIndex++;const options=inventory.map(x=>`<option value="${x.id}">${x.label}</option>`).join('');document.getElementById('components').insertAdjacentHTML('beforeend',`<div class="line"><select name="items[${i}][inventory_item_id]" required><option value="">Select inventory item</option>${options}</select><input type="number" name="items[${i}][quantity_required]" value="1" min="1" required><button type="button" class="secondary" onclick="this.parentElement.remove()">×</button></div>`)}
addComponent();
</script>
</body></html>
