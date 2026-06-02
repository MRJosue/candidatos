<section class="bg-white p-6 rounded shadow-sm space-y-5">
    <div>
        <h3 class="text-lg font-semibold text-gray-900">Secciones del CV</h3>
        <p class="text-sm text-gray-500">Edita la informacion principal que usa el cliente. En experiencia y educacion usa el formato: Titulo | Organizacion | Periodo. En habilidades puedes separar elementos con punto y coma.</p>
    </div>

    <label class="block">
        <span class="text-sm font-medium text-gray-700">Habilidades</span>
        <textarea name="skills_text" rows="7" class="mt-1 w-full rounded border-gray-300" placeholder="PHP; JavaScript; SQL">{{ old('skills_text', $sectionText['skills'] ?? '') }}</textarea>
    </label>

    <label class="block">
        <span class="text-sm font-medium text-gray-700">Experiencia</span>
        <textarea name="experiences_text" rows="8" class="mt-1 w-full rounded border-gray-300" placeholder="Tech Lead | Acme Software | 2021 - presente&#10;Descripcion del rol">{{ old('experiences_text', $sectionText['experiences'] ?? '') }}</textarea>
    </label>

    <label class="block">
        <span class="text-sm font-medium text-gray-700">Educacion</span>
        <textarea name="education_text" rows="6" class="mt-1 w-full rounded border-gray-300" placeholder="Ingenieria en Sistemas | Universidad Demo | 2017 - 2021&#10;Notas opcionales">{{ old('education_text', $sectionText['education'] ?? '') }}</textarea>
    </label>

    <div class="grid md:grid-cols-3 gap-4">
        <label class="block">
            <span class="text-sm font-medium text-gray-700">Software</span>
            <textarea name="software_text" rows="7" class="mt-1 w-full rounded border-gray-300" placeholder="Jira; GitHub; Figma">{{ old('software_text', $sectionText['software'] ?? '') }}</textarea>
        </label>
        <label class="block">
            <span class="text-sm font-medium text-gray-700">Idiomas</span>
            <textarea name="languages_text" rows="7" class="mt-1 w-full rounded border-gray-300" placeholder="Espanol; Ingles">{{ old('languages_text', $sectionText['languages'] ?? '') }}</textarea>
        </label>
        <label class="block">
            <span class="text-sm font-medium text-gray-700">Certificaciones</span>
            <textarea name="certifications_text" rows="7" class="mt-1 w-full rounded border-gray-300" placeholder="AWS Certified Cloud Practitioner; Scrum Master">{{ old('certifications_text', $sectionText['certifications'] ?? '') }}</textarea>
        </label>
    </div>

    @error('experiences_text')
        <p class="text-sm text-red-700">{{ $message }}</p>
    @enderror
</section>
