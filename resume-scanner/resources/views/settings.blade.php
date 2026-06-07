@extends($settingsLayout)

@php $active = $active ?? 'settings'; @endphp

@section('content')
    @php $user = auth()->user(); @endphp
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card p-6 xl:col-span-2">
            @if (session('success'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="font-semibold">Please fix the highlighted fields and try again.</p>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="inline-flex rounded-full bg-accentSoft px-3 py-1 text-xs font-semibold text-accent uppercase tracking-[0.16em]">Profile</div>
                    <h2 class="mt-4 text-2xl font-bold text-text">Account settings</h2>
                    <p class="mt-2 text-sm text-muted">Manage identity, password, and profile preferences without leaving your current portal.</p>
                </div>
            </div>

            <div class="mt-6 space-y-6">
                <form method="POST" action="{{ route('settings.update') }}" class="space-y-5" enctype="multipart/form-data" id="settingsForm">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-text mb-3">Profile picture</label>
                        <div class="flex items-center gap-4">
                            <div class="h-16 w-16 rounded-full bg-accentSoft text-accent flex items-center justify-center font-bold text-lg flex-shrink-0" id="avatarPreview">
                                @if ($user?->getAvatarUrl())
                                    <img src="{{ $user->getAvatarUrl() }}" class="h-full w-full rounded-full object-cover" alt="Profile picture" />
                                @else
                                    {{ $user?->getInitials() }}
                                @endif
                            </div>
                            <div class="flex-1">
                                <input type="file" id="profilePictureInput" name="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp" class="mt-2 block w-full rounded-xl border border-border bg-white px-3 py-2 text-xs text-text" />
                                <p class="mt-2 text-xs text-muted">JPG, PNG, GIF or WEBP. Max 5MB.</p>
                                <p class="mt-1 text-xs text-muted">After selecting image, click Save changes.</p>
                            </div>
                        </div>
                        @error('profile_picture')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <input name="name" value="{{ old('name', $user?->name ?? '') }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-text" placeholder="Your name" required />
                        <input name="email" type="email" value="{{ old('email', $user?->email ?? '') }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-text" placeholder="Your email" required />
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <input name="password" type="password" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-text" placeholder="New password" />
                        <input name="password_confirmation" type="password" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-text" placeholder="Confirm new password" />
                    </div>

                    <div class="mt-5 space-y-3">
                        <label class="flex items-center justify-between rounded-2xl border border-border px-4 py-4">
                            <span class="text-sm text-text">Email notifications</span>
                            <input type="checkbox" class="h-5 w-5 rounded border-border" />
                        </label>
                        <label class="flex items-center justify-between rounded-2xl border border-border px-4 py-4">
                            <span class="text-sm text-text">Remember filters</span>
                            <input type="checkbox" checked class="h-5 w-5 rounded border-border" />
                        </label>
                    </div>

                    <button type="submit" class="nav-btn-primary mt-6 h-11 px-5 text-sm font-semibold">Save changes</button>
                </form>
            </div>
        </section>

        <aside class="space-y-6">
            <div class="card p-6">
                <h3 class="font-semibold text-text">Security</h3>
                <p class="mt-2 text-sm text-muted">Use a strong password and log out on shared devices.</p>
            </div>

            <div class="card p-6">
                <h3 class="font-semibold text-text">Links</h3>
                <div class="mt-4 space-y-3">
                    <a href="{{ route('privacy') }}" class="block rounded-xl border border-border px-4 py-3 text-sm text-text">Privacy Policy</a>
                    <a href="{{ route('terms') }}" class="block rounded-xl border border-border px-4 py-3 text-sm text-text">Terms & Conditions</a>
                </div>
            </div>
        </aside>
    </div>

    <script>
        const profilePictureInput = document.getElementById('profilePictureInput');
        const avatarPreview = document.getElementById('avatarPreview');

        function handleProfilePictureSelection(file) {
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(event) {
                avatarPreview.innerHTML = `<img src="${event.target.result}" class="h-full w-full rounded-full object-cover" alt="Preview" />`;
            };
            reader.readAsDataURL(file);
        }

        profilePictureInput?.addEventListener('change', function(e) {
            handleProfilePictureSelection(e.target.files?.[0]);
        });
    </script>
@endsection