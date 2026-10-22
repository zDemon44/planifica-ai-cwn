<x-layouts.aplicacion title="Catálogo curricular">
    <div class="page-heading">
        <div><p class="eyebrow">CONFIGURACIÓN ACADÉMICA</p><h1>Catálogo curricular</h1><p>Administra las destrezas y sus indicadores relacionados para cada materia y grado.</p></div>
    </div>

    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="delete-confirmation" role="alert"><strong>No se pudo importar el catálogo.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="panel curriculum-import">
        <div class="panel-heading"><div><h2>Importar desde Excel</h2><p>El archivo debe conservar las columnas y los grupos numerados del formato institucional.</p></div></div>
        <form method="POST" action="{{ route('catalogo-curricular.importar') }}" enctype="multipart/form-data" class="form-grid">
            @csrf
            <div class="field"><label for="materia_id_importar">Materia *</label><select id="materia_id_importar" name="materia_id" required><option value="">Selecciona una materia</option>@foreach($materias as $materia)<option value="{{ $materia->id }}" @selected((int) old('materia_id', $materiaId) === $materia->id)>{{ $materia->nombre }}</option>@endforeach</select></div>
            <div class="field"><label for="archivo">Archivo Excel (.xlsx) *</label><input id="archivo" name="archivo" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required><small>Se importan las adaptaciones de 2.º, 3.º y 4.º grado.</small></div>
            <div class="form-actions curriculum-import-action"><button class="primary-button" type="submit">Importar catálogo</button></div>
        </form>
    </section>

    <section class="panel record-list curriculum-list">
        <div class="panel-heading"><div><h2>Destrezas e indicadores</h2><p>{{ $destrezas->count() }} destrezas activas para el filtro actual.</p></div></div>
        <form method="GET" action="{{ route('catalogo-curricular.index') }}" class="list-toolbar">
            <div class="field"><label for="materia_id">Materia</label><select id="materia_id" name="materia_id">@foreach($materias as $materia)<option value="{{ $materia->id }}" @selected($materiaId === $materia->id)>{{ $materia->nombre }}</option>@endforeach</select></div>
            <div class="field"><label for="grado">Grado</label><select id="grado" name="grado">@foreach([2 => '2.º grado', 3 => '3.º grado', 4 => '4.º grado'] as $numero => $etiqueta)<option value="{{ $numero }}" @selected($grado === $numero)>{{ $etiqueta }}</option>@endforeach</select></div>
            <button class="primary-button" type="submit">Filtrar</button>
        </form>
        <div class="curriculum-groups">
            @forelse($destrezas->groupBy('grupo') as $grupo => $registros)
                <article class="curriculum-group">
                    <header><span>Grupo {{ $grupo }}</span><small>{{ $registros->count() }} destreza{{ $registros->count() === 1 ? '' : 's' }}</small></header>
                    @foreach($registros as $destreza)
                        <div class="curriculum-skill">
                            <div><strong>{{ $destreza->codigo }}</strong>@foreach($destreza->competencias as $competencia)<span class="curriculum-competence">{{ $competencia->codigo }}</span>@endforeach<p>{{ $destreza->descripcion }}</p></div>
                            <div class="curriculum-indicators"><h3>Indicadores relacionados</h3>@foreach($destreza->indicadores as $indicador)<div><strong>{{ $indicador->codigo }}</strong>@foreach($indicador->competencias as $competencia)<span class="curriculum-competence">{{ $competencia->codigo }}</span>@endforeach<p>{{ $indicador->descripcion }}</p></div>@endforeach</div>
                        </div>
                    @endforeach
                </article>
            @empty
                <div class="empty-state"><h2>Aún no hay información curricular</h2><p>Selecciona la materia e importa su archivo Excel.</p></div>
            @endforelse
        </div>
    </section>
</x-layouts.aplicacion>
