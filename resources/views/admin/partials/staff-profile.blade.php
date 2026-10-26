<fieldset class="vstack gap-3">
<legend class="h5">Apresentação profissional</legend>
<p class="small text-secondary">A fotografia e a apresentação podem ser mostradas aos clientes nos serviços e marcações públicas.</p>
@if($profile?->avatarUrl())
<div class="d-flex flex-column align-items-center gap-2 w-100">
<a href="{{ $profile->avatarUrl() }}" target="_blank" rel="noopener noreferrer" aria-label="Abrir fotografia de perfil"><img src="{{ $profile->avatarUrl() }}" alt="Fotografia de perfil" width="96" height="96" style="display:block;width:96px;height:96px;max-width:100%;border-radius:50%;object-fit:cover;object-position:center"></a>
<label><input type="checkbox" name="remove_avatar" value="1"> Remover fotografia atual</label>
</div>
@endif
<label>Fotografia de perfil<input class="form-control" type="file" name="avatar" accept="image/jpeg,image/png,image/webp">@error('avatar')<span class="text-danger d-block">{{ $message }}</span>@enderror<small>JPG, PNG ou WebP, até 2 MB. Deixe vazio para manter a fotografia.</small></label>
<label>Apresentação do profissional<textarea class="form-control" name="biography" maxlength="2000" rows="4" placeholder="Especialidades, experiência e uma breve apresentação">{{ old('biography',$profile?->biography) }}</textarea>@error('biography')<span class="text-danger d-block">{{ $message }}</span>@enderror</label>
</fieldset>
