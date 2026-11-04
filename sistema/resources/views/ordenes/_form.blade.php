{{--
    Campos compartidos por los formularios de órdenes.
--}}
<div class="row g-3">
    <div class="col-md-6">
        <label for="cliente_id" class="form-label">Cliente *</label>
        <select class="form-select @error('cliente_id') is-invalid @enderror" id="cliente_id" name="cliente_id" required>
            <option value="">Seleccione un cliente</option>
            @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}" @selected((string) old('cliente_id', $orden->cliente_id) === (string) $cliente->id)>
                    {{ $cliente->nombre }} ({{ $cliente->tipoNombre() }})
                </option>
            @endforeach
        </select>
        @error('cliente_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="user_id" class="form-label">Responsable</label>
        <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id">
            <option value="">Sin asignar</option>
            @foreach ($responsables as $responsable)
                <option value="{{ $responsable->id }}" @selected((string) old('user_id', $orden->user_id) === (string) $responsable->id)>
                    {{ $responsable->name }} — {{ $responsable->rolNombre() }}
                </option>
            @endforeach
        </select>
        @error('user_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="paciente" class="form-label">Paciente</label>
        <input type="text" class="form-control @error('paciente') is-invalid @enderror" id="paciente" name="paciente"
               value="{{ old('paciente', $orden->paciente) }}" maxlength="120" placeholder="Nombre del paciente (opcional)">
        @error('paciente')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="tipo_trabajo" class="form-label">Tipo de trabajo *</label>
        <input type="text" class="form-control @error('tipo_trabajo') is-invalid @enderror" id="tipo_trabajo"
               name="tipo_trabajo" value="{{ old('tipo_trabajo', $orden->tipo_trabajo) }}" maxlength="100"
               list="tipos-trabajo" required>
        <datalist id="tipos-trabajo">
            <option value="Corona de zirconio"></option>
            <option value="Corona de porcelana"></option>
            <option value="Corona metal porcelana"></option>
            <option value="Puente fijo"></option>
            <option value="Prótesis parcial removible"></option>
            <option value="Prótesis total"></option>
            <option value="Incrustación"></option>
            <option value="Carilla de porcelana"></option>
            <option value="Aparato de ortodoncia"></option>
            <option value="Reparación de prótesis"></option>
        </datalist>
        @error('tipo_trabajo')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="fecha_ingreso" class="form-label">Fecha de ingreso *</label>
        <input type="date" class="form-control @error('fecha_ingreso') is-invalid @enderror" id="fecha_ingreso"
               name="fecha_ingreso" value="{{ old('fecha_ingreso', $orden->fecha_ingreso?->format('Y-m-d')) }}" required>
        @error('fecha_ingreso')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="fecha_entrega" class="form-label">Fecha de entrega</label>
        <input type="date" class="form-control @error('fecha_entrega') is-invalid @enderror" id="fecha_entrega"
               name="fecha_entrega" value="{{ old('fecha_entrega', $orden->fecha_entrega?->format('Y-m-d')) }}">
        @error('fecha_entrega')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="precio" class="form-label">Precio del trabajo (S/) *</label>
        <input type="number" step="0.01" min="0" class="form-control @error('precio') is-invalid @enderror"
               id="precio" name="precio" value="{{ old('precio', $orden->precio ?? 0) }}" required>
        @error('precio')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="notas" class="form-label">Notas u observaciones</label>
        <textarea class="form-control @error('notas') is-invalid @enderror" id="notas" name="notas" rows="3"
                  maxlength="2000" placeholder="Indicaciones especiales del trabajo">{{ old('notas', $orden->notas) }}</textarea>
        @error('notas')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
