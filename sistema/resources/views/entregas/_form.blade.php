{{--
    Campos compartidos por los formularios de entregas.
--}}
<div class="row g-3">
    <div class="col-md-8">
        <label for="orden_trabajo_id" class="form-label">Orden de trabajo *</label>
        <select class="form-select @error('orden_trabajo_id') is-invalid @enderror" id="orden_trabajo_id"
                name="orden_trabajo_id" required>
            <option value="">Seleccione una orden</option>
            @foreach ($ordenes as $orden)
                <option value="{{ $orden->id }}" @selected((string) old('orden_trabajo_id', $entrega->orden_trabajo_id) === (string) $orden->id)>
                    {{ $orden->codigo }} · {{ $orden->cliente->nombre }} · {{ $orden->paciente ?? 'Sin paciente' }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Solo se muestran órdenes que aún no tienen entrega registrada.</div>
        @error('orden_trabajo_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="tipo" class="form-label">Tipo de entrega *</label>
        <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo" required>
            @foreach ($tipos as $valor => $nombre)
                <option value="{{ $valor }}" @selected(old('tipo', $entrega->tipo) === $valor)>{{ $nombre }}</option>
            @endforeach
        </select>
        @error('tipo')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="fecha" class="form-label">Fecha de entrega *</label>
        <input type="date" class="form-control @error('fecha') is-invalid @enderror" id="fecha" name="fecha"
               value="{{ old('fecha', $entrega->fecha?->format('Y-m-d')) }}" required>
        @error('fecha')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="receptor" class="form-label">Recibido por</label>
        <input type="text" class="form-control @error('receptor') is-invalid @enderror" id="receptor" name="receptor"
               value="{{ old('receptor', $entrega->receptor) }}" maxlength="150" placeholder="Nombre de quien recibe">
        @error('receptor')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" id="confirmado" name="confirmado" value="1"
                   {{ old('confirmado', $entrega->confirmado) ? 'checked' : '' }}>
            <label class="form-check-label" for="confirmado">Entrega confirmada</label>
        </div>
    </div>

    <div class="col-12">
        <label for="observacion" class="form-label">Observación</label>
        <textarea class="form-control @error('observacion') is-invalid @enderror" id="observacion" name="observacion"
                  rows="2" maxlength="1000">{{ old('observacion', $entrega->observacion) }}</textarea>
        @error('observacion')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
