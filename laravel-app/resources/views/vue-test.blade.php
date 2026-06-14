<x-layouts.app title="Vue-Test">
    <section class="space-y-6">
        <div>
            <h1 class="text-3xl font-bold">Vue-Test</h1>
            <p class="mt-2 text-slate-300">
                Diese Seite prüft, ob Vue 3 über Vite innerhalb von Laravel funktioniert.
            </p>
        </div>

        <div id="app-status" data-app-name="{{ config('app.name', 'stechen-mmo') }}"></div>
    </section>
</x-layouts.app>
