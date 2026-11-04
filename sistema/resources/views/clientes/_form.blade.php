{{--
    Campos compartidos por los formularios de alta y edición de clientes.
--}}
<div class="row g-3">
    <div class="col-md-4">
        <label for="tipo" class="form-label">Tipo de cliente *</label>
        <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo" required>
            <option value="odontologo" @selected(old('tipo', $cliente->tipo) === 'odontologo')>Odontólogo</option>
            <option value="clinica" @selected(old('tipo', $cliente->tipo) === 'clinica')>Clínica</option>
        </select>
        @error('tipo')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8">
        <label for="nombre" class="form-label">Nombre del odontólogo o clínica *</label>
        <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre"
               value="{{ old('nombre', $cliente->nombre) }}" maxlength="150" required>
        @error('nombre')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="telefono" class="form-label">Teléfono</label>
        <input type="text" class="form-control @error('telefono') is-invalid @enderror" id="telefono" name="telefono"
               value="{{ old('telefono', $cliente->telefono) }}" maxlength="30" placeholder="Ej. 984 123 456">
        @error('telefono')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
               value="{{ old('email', $cliente->email) }}" maxlength="150">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="user_id" class="form-label">Usuario del portal (opcional)</label>
        <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id">
            <option value="">Sin usuario vinculado</option>
            @foreach ($usuariosPortal as $usuario)
                <option value="{{ $usuario->id }}" @selected((string) old('user_id', $cliente->user_id) === (string) $usuario->id)>
                    {{ $usuario->name }} — {{ $usuario->email }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Permite que el odontólogo consulte solo los trabajos de este cliente.</div>
        @error('user_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="direccion" class="form-label">Dirección</label>
        <input type="text" class="form-control @error('direccion') is-invalid @enderror" id="direccion" name="direccion"
               value="{{ old('direccion', $cliente->direccion) }}" maxlength="200">
        @error('direccion')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if ($cliente->exists)
        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1"
                       {{ old('activo', $cliente->activo) ? 'checked' : '' }}>
                <label class="form-check-label" for="activo">Cliente activo</label>
            </div>
        </div>
    @endif
</div>
