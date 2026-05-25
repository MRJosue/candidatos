@if (! empty($documentImport))
    <div class="space-y-4 rounded border border-indigo-100 bg-indigo-50/60 p-4">
        <div>
            <h4 class="font-semibold text-gray-900">Aplicar datos detectados</h4>
            <p class="text-sm text-gray-600">Selecciona que partes de la previsualizacion quieres guardar en este CV.</p>
        </div>
        <input type="hidden" name="apply_document_import" value="1">
        <div class="grid md:grid-cols-3 gap-3 text-sm text-gray-700">
            <label class="flex items-center gap-2"><input type="checkbox" name="apply_profile" value="1" class="rounded border-gray-300" checked> Perfil</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="apply_experiences" value="1" class="rounded border-gray-300" checked> Experiencia</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="apply_education" value="1" class="rounded border-gray-300" checked> Educacion</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="apply_software" value="1" class="rounded border-gray-300" checked> Software</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="apply_skills" value="1" class="rounded border-gray-300" checked> Habilidades</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="apply_languages" value="1" class="rounded border-gray-300" checked> Idiomas</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="apply_soft_skills" value="1" class="rounded border-gray-300" checked> Habilidades blandas</label>
        </div>
    </div>
@endif
