<x-layouts.aplicacion :title="$recreo->exists ? 'Editar recreo' : 'Configurar recreo'">
    <div class="page-heading"><div><p class="eyebrow">FRANJA INSTITUCIONAL</p><h1>{{ $recreo->exists ? 'Editar recreo' : 'Configurar recreo' }}</h1><p>Se mostrará en todos los horarios del subnivel y no contará como clase.</p></div><a class="secondary-button" href="{{ route('recreos.index') }}">Volver</a></div>
    @if($errors->any())<div class="delete-confirmation" role="alert"><strong>Revisa la información.</strong><ul>@foreach($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $recreo->exists ? route('recreos.update', $recreo) : route('recreos.store') }}" class="schedule-form">
        @csrf @if($recreo->exists)@method('PUT')@endif
        <section class="panel">
            <div class="planning-section-heading"><span>01</span><div><h2>Aplicación</h2><p>El período y el subnivel determinan qué horarios reciben esta franja.</p></div></div>
            <div class="form-grid">
                <div class="field"><label for="periodo_academico_id">Período académico *</label><select id="periodo_academico_id" name="periodo_academico_id" required><option value="">Selecciona un período</option>@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected(old('periodo_academico_id', $recreo->periodo_academico_id) == $periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></div>
                <div class="field"><label for="subnivel">Subnivel *</label><select id="subnivel" name="subnivel" required><option value="">Selecciona un subnivel</option>@foreach($subniveles as $valor => $etiqueta)<option value="{{ $valor }}" @selected(old('subnivel', $recreo->subnivel) === $valor)>{{ $etiqueta }}</option>@endforeach</select></div>
                <div class="field"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" maxlength="60" value="{{ old('nombre', $recreo->nombre ?: 'Recreo') }}" required></div>
            </div>
        </section>
        <section class="panel">
            <div class="planning-section-heading"><span>02</span><div><h2>Horario</h2><p>Las clases no podrán cruzarse con esta franja.</p></div></div>
            <div class="form-grid">
                <div class="field"><label for="hora_inicio">Inicio *</label><input id="hora_inicio" name="hora_inicio" type="time" value="{{ old('hora_inicio', $recreo->hora_inicio ? substr($recreo->hora_inicio, 0, 5) : '09:20') }}" required></div>
                <div class="field"><label for="hora_fin">Fin *</label><input id="hora_fin" name="hora_fin" type="time" value="{{ old('hora_fin', $recreo->hora_fin ? substr($recreo->hora_fin, 0, 5) : '09:50') }}" required></div>
            </div>
            <fieldset class="recess-days-field"><legend>Días *</legend><div class="recess-day-options">@foreach(App\Models\Horario::DIAS as $numero => $dia)<label><input type="checkbox" name="dias_semana[]" value="{{ $numero }}" @checked(in_array($numero, old('dias_semana', $recreo->dias_semana ?? array_keys(App\Models\Horario::DIAS))))> {{ $dia }}</label>@endforeach</div></fieldset>
            <label class="recess-active"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" @checked(old('activo', $recreo->exists ? $recreo->activo : true))> Recreo activo</label>
        </section>
        <div class="form-actions"><a class="secondary-button" href="{{ route('recreos.index') }}">Cancelar</a><button class="primary-button" type="submit">Guardar recreo</button></div>
    </form>
</x-layouts.aplicacion>
