<x-app-layout
    header-eyebrow="Spielerkonto"
    :header-title="'Profil verwalten, '.Auth::user()->name"
    header-status-label="Konto aktiv"
    header-status-tone="success"
>
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20 sm:p-8">
            <div class="max-w-2xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20 sm:p-8">
            <div class="max-w-2xl">
                @include('profile.partials.update-password-form')
            </div>
        </section>

        <section class="rounded-2xl border border-red-500/20 bg-red-500/10 p-6 shadow-xl shadow-black/20 sm:p-8">
            <div class="max-w-2xl">
                @include('profile.partials.delete-user-form')
            </div>
        </section>
    </div>
</x-app-layout>
