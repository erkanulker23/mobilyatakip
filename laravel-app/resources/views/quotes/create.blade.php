@extends('layouts.app')
@section('title', 'Yeni Teklif')
@section('content')
<div class="header"><h1>Yeni Teklif</h1></div>
<form method="POST" action="{{ route('quotes.store') }}">
    @csrf
    <div class="form-group"><label>Müşteri *</label><select name="customerId" required>@foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
    <div class="form-group"><label>Personel</label><select name="personnelId"><option value="">-</option>@foreach($personnel as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
    <div class="form-group"><label>Geçerlilik</label><input type="date" name="validUntil" value="{{ old('validUntil') }}"></div>
    <div class="card"><h3>Kalemler</h3>
        <div id="items">
            <div class="form-group" style="display:grid;grid-template-columns:1fr 100px 80px 80px auto;gap:0.5rem;align-items:end;">
                <label>Ürün</label><label>Fiyat</label><label>Adet</label><label>KDV%</label><span></span>
            </div>
            <div class="item-row form-group" style="display:grid;grid-template-columns:1fr 100px 80px 80px auto;gap:0.5rem;align-items:end;">
                <select name="items[0][productId]" required>@foreach($products as $p)<option value="{{ $p->id }}" data-price="{{ $p->unitPrice }}" data-kdv="{{ $p->kdvRate }}">{{ $p->name }} ({{ number_format($p->unitPrice, 2) }} ₺)</option>@endforeach</select>
                <input type="number" step="0.01" name="items[0][unitPrice]" required placeholder="0">
                <input type="number" name="items[0][quantity]" value="1" required min="1">
                <input type="number" step="0.01" name="items[0][kdvRate]" value="18" placeholder="18">
                <button type="button" class="btn btn-secondary" onclick="addRow()">+</button>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Teklif Oluştur</button>
    <a href="{{ route('quotes.index') }}" class="btn btn-secondary">İptal</a>
</form>
<script>
let idx=1;
function addRow(){const t=document.querySelector('.item-row');const c=t.cloneNode(true);c.querySelectorAll('select, input').forEach((e,i)=>{e.name=e.name.replace(/\[\d+\]/,'['+idx+']');e.value=e.type==='number'?1:'';});document.getElementById('items').appendChild(c);idx++;}
document.querySelectorAll('select[name^="items"]').forEach(s=>{s.addEventListener('change',function(){const o=this.selectedOptions[0];if(o){this.closest('.item-row').querySelector('input[name*="unitPrice"]').value=o.dataset.price||'';this.closest('.item-row').querySelector('input[name*="kdvRate"]').value=o.dataset.kdv||18;}});});
</script>
@endsection
