<section class="space-y-6">
    <header>
        <p class="text-sm font-medium uppercase tracking-wide text-red-300">
            Gefahrenzone
        </p>

        <h2 class="mt-1 text-xl font-bold text-red-100">
            Konto löschen
        </h2>

        <p class="mt-2 text-sm leading-6 text-red-100/80">
            Wenn dein Konto gelöscht wird, werden alle zugehörigen Daten endgültig gelöscht. Sichere vorher alle Informationen, die du behalten möchtest.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Konto löschen</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold text-red-100">
                Möchtest du dein Konto wirklich löschen?
            </h2>

            <p class="mt-2 text-sm leading-6 text-slate-400">
                Wenn dein Konto gelöscht wird, werden alle zugehörigen Daten endgültig gelöscht. Gib zur Bestätigung dein Passwort ein.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Passwort" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Passwort"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Abbrechen
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Konto löschen
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
