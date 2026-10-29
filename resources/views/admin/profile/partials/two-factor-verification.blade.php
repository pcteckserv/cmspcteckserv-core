<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="two-factor-password">Palavra-passe atual</label>
        <input class="form-control @error('current_password') is-invalid @enderror" id="two-factor-password" name="current_password" type="password" autocomplete="current-password" required>
        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="two-factor-code">Código de autenticação</label>
        <input class="form-control @error('two_factor_code') is-invalid @enderror" id="two-factor-code" name="two_factor_code" type="text" autocomplete="one-time-code" maxlength="64" required>
        <div class="form-text">Use o código da aplicação ou, se já ativou o 2FA, um código de recuperação.</div>
    </div>
</div>
