{{--
    Campos compartidos por los formularios de materiales.
--}}
<div class="row g-3">
    <div class="col-md-6">
        <label for="nombre" class="form-label">Nombre del material *</label>
        <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre"
               value="{{ old('nombre', $material->nombre) }}" maxlength="150" required>
        @error('nombre')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="unidad" class="form-label">Unidad *</label>
        <input type="text" class="form-control @error('unidad') is-invalid @enderror" id="unidad" name="unidad"
               value="{{ old('unidad', $material->unidad) }}" maxlength="30" list="unidades" required>
        <datalist id="unidades">
            <option value="unidad"></option>
            <option value="gramo"></option>
            <option value="kilogramo"></option>
            <option value="mililitro"></option>
            <option value="litro"></option>
            <option value="caja"></option>
        </datalist>
        @error('unidad')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if (auth()->user()->puedeVerCostos())
        <div class="col-md-3">
            <label for="costo" class="form-label">Costo unitario (S/) *</label>
            <input type="number" step="0.01" min="0" class="form-control @error('costo') is-invalid @enderror"
                   id="costo" name="costo" value="{{ old('costo', $material->costo) }}" required>
            @error('costo')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    @endif

    <div class="col-md-4">
        <label for="stock" class="form-label">Stock actual *</label>
        <input type="number" step="0.01" min="0" class="form-control @error('stock') is-invalid @enderror"
               id="stock" name="stock" value="{{ old('stock', $material->stock ?? 0) }}" required>
        @error('stock')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="stock_minimo" class="form-label">Stock mínimo *</label>
        <input type="number" step="0.01" min="0" class="form-control @error('stock_minimo') is-invalid @enderror"
               id="stock_minimo" name="stock_minimo" value="{{ old('stock_minimo', $material->stock_minimo ?? 0) }}" required>
        <div class="form-text">Se alertará cuando el stock llegue a este valor o menos.</div>
        @error('stock_minimo')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if ($material->exists)
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1"
                       {{ old('activo', $material->activo) ? 'checked' : '' }}>
                <label class="form-check-label" for="activo">Material activo</label>
            </div>
        </div>
    @endif
</div>
