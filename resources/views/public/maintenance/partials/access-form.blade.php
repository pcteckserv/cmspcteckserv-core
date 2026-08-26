@if ($maintenance['access_enabled'])
    <form class="maintenance-access" method="POST" action="{{ route('maintenance.access') }}">
        @csrf
        <label for="maintenance-access-code">Tem acesso antecipado?</label>
        <div class="maintenance-access__row">
            <input id="maintenance-access-code" name="access_code" type="text" autocomplete="one-time-code" placeholder="Código de acesso" required>
            <button type="submit">Aceder</button>
        </div>
        @error('access_code')
            <p class="maintenance-error" role="alert" aria-live="polite">{{ $message }}</p>
        @enderror
    </form>
@endif
