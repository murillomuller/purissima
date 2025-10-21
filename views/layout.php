<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Purissima' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3F1F20',
                        secondary: '#2A1415',
                        accent: '#5A2F31'
                    }
                }
            }
        }
    </script>
    <style>
        /* Ensure proper height handling on mobile devices */
        @supports (height: 100dvh) {
            .h-screen {
                height: 100dvh;
            }
        }

        /* Smooth scrolling for sidebar navigation */
        .sidebar-nav {
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.5);
            border-radius: 2px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background-color: rgba(156, 163, 175, 0.7);
        }

        /* Enhanced hover effects */
        .nav-item {
            position: relative;
            overflow: hidden;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .nav-item:hover::before {
            left: 100%;
        }

        /* Tooltip styles for collapsed sidebar */
        .nav-item::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease-in-out;
            z-index: 1000;
            margin-left: 0.5rem;
        }

        .sidebar[data-collapsed="true"] .nav-item:hover::after {
            opacity: 1;
            visibility: visible;
        }

        /* Header auto-adjustment */
        .sticky-header {
            backdrop-filter: blur(8px);
            background: linear-gradient(to right, rgba(255, 255, 255, 0.95), rgba(249, 250, 251, 0.95));
            transition: all 0.3s ease-in-out;
            width: 100%;
        }

        /* Header adjusts when sidebar collapsed */
        body.sidebar-collapsed .sticky-header {
            width: calc(100vw - 4rem);
            margin-left: 0;
        }

        /* Header adjusts when sidebar expanded */
        body:not(.sidebar-collapsed) .sticky-header {
            width: calc(100vw - 16rem);
            margin-left: 0;
        }

        /* Ensure full width on mobile */
        @media (max-width: 1023px) {
            .sticky-header {
                width: 100vw !important;
                margin-left: 0 !important;
            }
        }

        /* Header content responsiveness */
        .header-content {
            transition: all 0.3s ease-in-out;
        }

        /* Adjust header content when sidebar collapsed */
        body.sidebar-collapsed .header-content {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        /* Adjust header content when sidebar expanded */
        body:not(.sidebar-collapsed) .header-content {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        /* Collapsed sidebar styles */
        .sidebar[data-collapsed="true"] .nav-item {
            justify-content: center;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .sidebar[data-collapsed="true"] .nav-item svg {
            margin: 0;
        }

        /* Header layout for expanded state */
        #sidebar-header {
            justify-content: space-between;
        }

        /* Center logo when collapsed */
        .sidebar[data-collapsed="true"] #sidebar-header {
            justify-content: center;
            position: relative;
        }

        .sidebar[data-collapsed="true"] #sidebar-brand {
            justify-content: center;
            width: 100%;
        }

        .sidebar[data-collapsed="true"] #sidebar-logo {
            margin: 0 auto;
        }

        .sidebar[data-collapsed="true"] #sidebar-toggle {
            position: absolute;
            right: 1rem;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Sidebar -->
    <div id="sidebar" class="bg-white shadow-xl border-r border-gray-200 transition-all duration-300 ease-in-out fixed inset-y-0 left-0 z-50 -translate-x-full lg:translate-x-0 h-screen w-64">
        <!-- Sidebar Header -->
        <div id="sidebar-header" class="flex items-center justify-center p-5 border-b border-gray-200 transition-all duration-300 bg-gradient-to-r from-primary to-secondary">
            <div id="sidebar-brand" class="flex items-center space-x-3 transition-all duration-300">
                <img id="sidebar-logo" src="/images/purissima-logo.png" alt="Purissima Logo" class="h-16 w-auto filter brightness-0 invert transition-all duration-300">
            </div>
            <button id="sidebar-toggle" class="p-2 rounded-lg hover:bg-white/20 transition-all duration-200 group">
                <svg class="w-5 h-5 text-white transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="flex-1 p-4 overflow-y-auto sidebar-nav">
            <ul class="space-y-1">
                <li>
                    <a href="/" class="nav-item flex items-center space-x-3 px-3 py-3 rounded-xl text-gray-700 hover:bg-primary hover:text-white transition-all duration-200 group relative overflow-hidden" data-tooltip="Pedidos">
                        <div class="absolute inset-0 bg-gradient-to-r from-primary to-secondary opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
                        <svg class="w-5 h-5 relative z-10 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="sidebar-text relative z-10 transition-opacity duration-300 font-medium">Pedidos</span>
                    </a>
                </li>
                <li>
                    <a href="/production" class="nav-item flex items-center space-x-3 px-3 py-3 rounded-xl text-gray-700 hover:bg-primary hover:text-white transition-all duration-200 group relative overflow-hidden" data-tooltip="Produção">
                        <div class="absolute inset-0 bg-gradient-to-r from-primary to-secondary opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
                        <svg class="w-5 h-5 relative z-10 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                        <span class="sidebar-text relative z-10 transition-opacity duration-300 font-medium">Produção</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div id="main-content" class="lg:pl-64 transition-all duration-300 ease-in-out">
        <!-- Top Bar -->
        <div class="bg-gradient-to-r from-white to-gray-50 shadow-lg border-b border-gray-200 px-4 sm:px-6 py-4 sm:py-6 sticky top-0 z-40 sticky-header max-w-full">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between header-content gap-4 sm:gap-0">
                <div class="flex items-center space-x-3 sm:space-x-4 w-full sm:w-auto">
                    <button id="mobile-sidebar-toggle" class="p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200 lg:hidden">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex items-center space-x-2 sm:space-x-3 flex-1 min-w-0">
                        <div class="p-2 bg-primary/10 rounded-lg flex-shrink-0">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-primary truncate"><?= $title ?? 'Dashboard' ?></h1>
                            <p class="text-gray-600 text-xs sm:text-sm mt-1 truncate">Purissima Saúde Personalizada</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-4 w-full sm:w-auto justify-between sm:justify-end">
                    <div class="flex items-center space-x-2 text-xs sm:text-sm text-gray-500">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="hidden xs:inline"><?= date('d/m/Y H:i') ?></span>
                        <span class="xs:hidden"><?= date('d/m H:i') ?></span>
                    </div>
                    <button class="p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.5 5.5L9 10l-4.5 4.5L1 10l3.5-4.5z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <main class="bg-gray-50 min-h-screen">
            <?= $content ?>
        </main>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

    <script>
        // Sidebar functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const mobileSidebarToggle = document.getElementById('mobile-sidebar-toggle');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const sidebarTexts = document.querySelectorAll('.sidebar-text');
        const sidebarHeader = document.getElementById('sidebar-header');
        const sidebarBrand = document.getElementById('sidebar-brand');
        const mainContent = document.getElementById('main-content');

        let isCollapsed = false;

        function toggleSidebar() {
            isCollapsed = !isCollapsed;
            const sidebarLogo = document.getElementById('sidebar-logo');

            if (isCollapsed) {
                // Sidebar collapse
                sidebar.classList.add('w-16');
                sidebar.classList.remove('w-64');
                sidebarTexts.forEach(text => text.classList.add('opacity-0'));
                sidebarLogo.classList.remove('opacity-0');
                sidebarHeader.classList.remove('justify-between');
                sidebarHeader.classList.add('justify-center');
                sidebarToggle.querySelector('svg').style.transform = 'rotate(180deg)';

                // Main content margin adjustment
                mainContent.classList.remove('lg:pl-64');
                mainContent.classList.add('lg:pl-16');

                // Add collapsed state
                sidebar.setAttribute('data-collapsed', 'true');
                document.body.classList.add('sidebar-collapsed');

            } else {
                // Sidebar expand
                sidebar.classList.remove('w-16');
                sidebar.classList.add('w-64');
                sidebarTexts.forEach(text => text.classList.remove('opacity-0'));
                sidebarLogo.classList.remove('opacity-0');
                sidebarHeader.classList.remove('justify-center');
                sidebarHeader.classList.add('justify-between');
                sidebarToggle.querySelector('svg').style.transform = 'rotate(0deg)';

                // Main content margin adjustment
                mainContent.classList.remove('lg:pl-16');
                mainContent.classList.add('lg:pl-64');

                // Remove collapsed state
                sidebar.removeAttribute('data-collapsed');
                document.body.classList.remove('sidebar-collapsed');
            }
        }

        function toggleMobileSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }

        // Close mobile sidebar when clicking on a link
        document.querySelectorAll('#sidebar a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    toggleMobileSidebar();
                }
            });
        });

        // Event listeners
        sidebarToggle.addEventListener('click', toggleSidebar);
        mobileSidebarToggle.addEventListener('click', toggleMobileSidebar);
        sidebarOverlay.addEventListener('click', toggleMobileSidebar);

        // Initialize sidebar
        sidebar.classList.add('w-64');
    </script>

    <footer class="bg-white border-t mt-12">
        <div class="w-full py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 text-sm">
                &copy; <?= date('Y') ?> Purissima. Desenvolvido com PHP e tecnologias web modernas.
            </p>
        </div>
    </footer>
</body>

</html>