<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F8FAFC]">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' — ' : '' }}LicenseTrack | {{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}</title>
        <link rel="icon" type="image/png" href="{{ asset('logo cuma diamond.png') }}">

        <!-- Fonts (Sora & Figtree) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sora:500,600&display=swap" rel="stylesheet">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-sm text-slate-800 h-full bg-[#F8FAFC]" 
          x-data="{ 
              mobileSidebarOpen: false,
              sidebarCollapsed: localStorage.getItem('sidebar_collapsed') === 'true',
              toggleSidebar() {
                  this.sidebarCollapsed = !this.sidebarCollapsed;
                  localStorage.setItem('sidebar_collapsed', this.sidebarCollapsed);
              }
          }">
        <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">
            {{-- Navigation includes Desktop Dark Sidebar & Mobile Slide-over --}}
            @include('layouts.navigation')

            {{-- Main Scrollable Content Area --}}
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
                {{-- Top Header Bar --}}
                @include('layouts.topbar')

                {{-- Page Scroll Container --}}
                <div class="flex-1 overflow-y-auto">

                    @if (session('success') || session('status') || session('error'))
                        @php
                            $toastType = session('error') ? 'error' : 'success';
                            $toastMessage = session('error') ?? session('success') ?? session('status');
                            $toastTitle = session('error') ? 'Gagal!' : 'Berhasil!';

                            $skipToast = false;
                            if ($toastMessage && (
                                str_contains($toastMessage, 'berhasil dihapus')
                            )) {
                                $skipToast = true;
                            }
                        @endphp
                        @if (!$skipToast)
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 4000,
                                        timerProgressBar: true
                                    });
                                    Toast.fire({
                                        icon: '{{ $toastType }}',
                                        title: '{{ $toastTitle }}',
                                        text: {!! json_encode($toastMessage) !!}
                                    });
                                });
                            </script>
                        @endif
                    @endif

                    <main class="p-6 sm:p-8">
                        {{ $slot }}
                    </main>

                    <footer class="max-w-none mx-auto w-full px-6 sm:px-8 py-6 text-xs text-slate-400 border-t border-slate-200/60 flex items-center justify-between">
                        <span>LicenseTrack · {{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }} · v1.0</span>
                        <span>Sistem Pemantauan Lisensi & Gateway Reminder</span>
                    </footer>
                </div>
            </div>
        </div>

        <script>
            function confirmSaveSettings(event, form) {
                event.preventDefault();
                Swal.fire({
                    title: "Simpan perubahan?",
                    text: "Apakah Anda yakin ingin menyimpan perubahan pengaturan ini?",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#4f46e5",
                    cancelButtonColor: "#6b7280",
                    confirmButtonText: "Simpan",
                    cancelButtonText: "Batal",
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "Tersimpan!",
                            text: "Pengaturan berhasil diperbarui.",
                            icon: "success",
                            confirmButtonColor: "#4f46e5"
                        }).then(() => {
                            form.submit();
                        });
                    }
                });
            }

            function confirmDeleteLicense(event, form) {
                event.preventDefault();
                Swal.fire({
                    title: "Apakah Anda yakin?",
                    text: "Data lisensi ini akan dipindahkan ke tong sampah!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#ef4444",
                    cancelButtonColor: "#6b7280",
                    confirmButtonText: "Ya, Hapus!",
                    cancelButtonText: "Batal",
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "Terhapus!",
                            text: "Data lisensi berhasil dipindahkan ke tong sampah.",
                            icon: "success",
                            confirmButtonColor: "#4f46e5"
                        }).then(() => {
                            form.submit();
                        });
                    }
                });
            }
        </script>
        @stack('scripts')
    </body>
</html>
