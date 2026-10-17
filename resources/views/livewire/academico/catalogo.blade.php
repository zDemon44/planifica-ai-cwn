<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">ORGANIZACIÓN ACADÉMICA</p>
            <h1>Cursos y períodos</h1>
            <p>Prepara los niveles, los años lectivos y sus paralelos.</p>
        </div><button class="primary-button" wire:click="create">+ Nuevo registro</button>
    </div>
    <div class="academic-tabs" role="group" aria-label="Catálogos académicos">
        @foreach (['cursos' => '1. Cursos', 'periodos' => '2. Períodos', 'secciones' => '3. Secciones'] as $clave => $etiqueta)
            <button class="{{ $tipoCatalogo === $clave ? 'primary-button' : 'secondary-button' }}"
                wire:click="tab('{{ $clave }}')"
                aria-pressed="{{ $tipoCatalogo === $clave ? 'true' : 'false' }}">{{ $etiqueta }}</button>
        @endforeach
    </div>
    @if (session('catalogo_correcto'))
        <p class="success-message" role="status">{{ session('catalogo_correcto') }}</p>
    @endif
    @error('catalogo')
        <p class="delete-confirmation" role="alert">{{ $message }}</p>
    @enderror
    @if ($registroEliminar)
        <div class="delete-confirmation">
            <p>¿Eliminar «{{ $registroEliminar->nombre }}»? Esta acción no se puede deshacer.</p>
            <div class="form-actions"><button class="secondary-button" wire:click="cancel">Cancelar</button><button
                    class="danger-button" wire:click="delete" wire:loading.attr="disabled">Eliminar</button></div>
        </div>
    @endif
    @if ($mostrarFormulario)
        <section class="panel record-editor">
            <h2>{{ $idEdicion ? 'Editar' : 'Crear' }}
                {{ ['cursos' => 'curso', 'periodos' => 'período', 'secciones' => 'sección'][$tipoCatalogo] }}</h2>
            <form wire:submit="save">
                <div class="form-grid">
                    <x-ui.entrada model="formulario.nombre" :label="$tipoCatalogo === 'secciones' ? 'Paralelo (ej. A)' : 'Nombre'" :required="true" maxlength="100" />
                    @if ($tipoCatalogo === 'cursos')
                        <x-ui.selector model="formulario.subnivel" label="Subnivel *" required>
                            <option value="">Selecciona un subnivel</option>
                            @foreach (App\Models\Curso::SUBNIVELES as $valor => $etiqueta)
                                <option value="{{ $valor }}">{{ $etiqueta }}</option>
                            @endforeach
                        </x-ui.selector>
                        <x-ui.entrada model="formulario.orden" label="Orden del nivel (1, 2, 3…)" type="number"
                            min="1" max="100" :required="true" />
                        <label><input type="checkbox" wire:model="formulario.es_final"> Último nivel: permite
                            graduación</label>
                    @elseif($tipoCatalogo === 'periodos')
                        <x-ui.entrada model="formulario.fecha_inicio" label="Inicio del período" type="date"
                            :required="true" />
                        <x-ui.entrada model="formulario.fecha_fin" label="Fin del período" type="date"
                            :required="true" />
                    @else
                        <x-ui.selector model="formulario.curso_id" label="Curso *" required>
                            <option value="">Selecciona un curso</option>
                            @foreach ($cursos as $curso)
                                <option value="{{ $curso->id }}">{{ $curso->nombre }}</option>
                            @endforeach
                        </x-ui.selector>
                        <x-ui.selector model="formulario.periodo_academico_id" label="Período *" required>
                            <option value="">Selecciona un período</option>
                            @foreach ($periodos->where('cerrado', false) as $periodo)
                                <option value="{{ $periodo->id }}">{{ $periodo->nombre }}</option>
                            @endforeach
                        </x-ui.selector>
                    @endif
                </div>
                <div class="form-actions"><button class="secondary-button" type="button"
                        wire:click="cancel">Cancelar</button><button class="primary-button" type="submit"
                        wire:loading.attr="disabled">Guardar</button></div>
            </form>
        </section>
    @endif
    <section class="panel record-list">
        <p class="muted">
            @if ($tipoCatalogo === 'cursos')
                El orden define el siguiente nivel para la promoción. El nivel final se registra al terminar la
                secuencia.
            @elseif($tipoCatalogo === 'periodos')
                Cierra el período después de registrar el resultado de cada matrícula. Los períodos cerrados no aceptan
                cambios.
            @else
                Cada sección combina un curso, un período y un paralelo. Las matrículas se asignan a estas secciones.
            @endif
        </p>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        @if ($tipoCatalogo === 'cursos')
                            <th>Orden</th>
                            <th>Curso</th>
                            <th>Subnivel</th>
                            <th>Nivel final</th>
                        @elseif($tipoCatalogo === 'periodos')
                            <th>Período</th>
                            <th>Fechas</th>
                        <th>Estado</th>@else<th>Curso</th>
                            <th>Período</th>
                            <th>Paralelo</th>
                        @endif
                        <th>
                            Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($tipoCatalogo === 'cursos' ? $cursos : ($tipoCatalogo === 'periodos' ? $periodos : $secciones)) as $registro)
                        <tr wire:key="{{ $tipoCatalogo }}-{{ $registro->id }}">
                            @if ($tipoCatalogo === 'cursos')
                                <td>{{ $registro->orden }}</td>
                                <td>{{ $registro->nombre }}</td>
                                <td>{{ App\Models\Curso::SUBNIVELES[$registro->subnivel] ?? 'Sin definir' }}</td>
                                <td>{{ $registro->es_final ? 'Sí' : 'No' }}</td>
                            @elseif($tipoCatalogo === 'periodos')
                                <td>{{ $registro->nombre }}</td>
                                <td>{{ $registro->fecha_inicio->format('d/m/Y') }} –
                                    {{ $registro->fecha_fin->format('d/m/Y') }}</td>
                                <td>{{ $registro->cerrado ? 'Cerrado' : 'Abierto' }}</td>
                            @else<td>{{ $registro->curso->nombre }}</td>
                                <td>{{ $registro->periodo->nombre }}</td>
                                <td>{{ $registro->nombre }}</td>
                            @endif
                            <td>
                                <div class="row-actions"><button
                                        wire:click="edit({{ $registro->id }})">Editar</button><button
                                        wire:click="confirmarEliminacion({{ $registro->id }})">Eliminar</button>
                                    @if ($tipoCatalogo === 'periodos' && !$registro->cerrado)
                                        <button wire:click="cerrarPeriodo({{ $registro->id }})"
                                            wire:confirm="¿Cerrar este período? No se podrán modificar sus matrículas.">Cerrar
                                            período</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty<tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <h2>Aún no hay registros</h2>
                                    <p>Usa Nuevo registro para configurar este catálogo.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
