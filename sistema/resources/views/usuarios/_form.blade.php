{{--
    Campos compartidos por los formularios de usuarios.
--}}
<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Nombre completo *</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
               value="{{ old('name', $usuario->name) }}" maxlength="150" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Correo electrónico *</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
               value="{{ old('email', $usuario->email) }}" maxlength="150" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="rol" class="form-label">Rol *</label>
        <select class="form-select @error('rol') is-invalid @enderror" id="rol" name="rol" required>
            @foreach ($roles as $valor => $nombre)
                <option value="{{ $valor }}" @selected(old('rol', $usuario->rol) === $valor)>{{ $nombre }}</option>
            @endforeach
        </select>
        @error('rol')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if ($usuario->exists)
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1"
                       {{ old('activo', $usuario->activo) ? 'checked' : '' }}>
                <label class="form-check-label" for="activo">Usuario activo</label>
            </div>
        </div>
    @endif

    <div class="col-md-6">
        <label for="password" class="form-label">
            Contraseña {{ $usuario->exists ? '(opcional)' : '*' }}
        </label>
        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password"
               autocomplete="new-password" {{ $usuario->exists ? '' : 'required' }}>
        <div class="form-text">Mínimo 8 caracteres.</div>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
               autocomplete="new-password" {{ $usuario->exists ? '' : 'required' }}>
    </div>
</div>
