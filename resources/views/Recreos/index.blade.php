<x-layouts.aplicacion title="Recreos">
    <div class="page-heading">
        <div><p class="eyebrow">ORGANIZACIÓN INSTITUCIONAL</p><h1>Recreos</h1><p>Define una franja por período académico y subnivel.</p></div>
        <div class="planning-toolbar-actions"><a class="secondary-button" href="{{ route('horarios.index') }}">Ver horarios</a><a class="primary-button" href="{{ route('recreos.create') }}">+ Configurar recreo</a></div>
    </div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    <section class="schedule-list">
        @forelse($recreos as $recreo)
            <article class="panel schedule-card recess-card">
                <div class="schedule-card-heading"><div><span class="course-badge">{{ $recreo->periodo->nombre }}</span><h2>{{ App\Models\Curso::SUBNIVELES[$recreo->subnivel] }}</h2><p>{{ $recreo->nombre }}</p></div><div class="schedule-stat"><strong>{{ substr($recreo->hora_inicio, 0, 5) }}</strong><span>hasta {{ substr($recreo->hora_fin, 0, 5) }}</span></div></div>
                <p class="recess-days">{{ collect($recreo->dias_semana)->map(fn ($dia) => App\Models\Horario::DIAS[$dia])->join(' · ') }}</p>
                <div class="form-actions"><span class="status-badge {{ $recreo->activo ? 'status-active' : 'status-inactive' }}">{{ $recreo->activo ? 'Activo' : 'Inactivo' }}</span><a class="secondary-button" href="{{ route('recreos.edit', $recreo) }}">Editar</a></div>
            </article>
        @empty
            <section class="panel schedule-empty"><h2>Aún no hay recreos configurados</h2><p>Registra la franja de Básica Elemental para mostrarla automáticamente en sus horarios.</p><a class="primary-button" href="{{ route('recreos.create') }}">Configurar el primer recreo</a></section>
        @endforelse
    </section>
    @if($recreos->hasPages()){{ $recreos->links() }}@endif
</x-layouts.aplicacion>
