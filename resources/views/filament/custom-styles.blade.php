<style>
    /* Modern Automotive ERP Styles */
    :root {
        --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    }

    body {
        font-feature-settings: "cv02", "cv03", "cv04", "cv11";
        -webkit-font-smoothing: antialiased;
    }

    /* Custom Scrollbars */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.4);
        border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(107, 114, 128, 0.7);
    }

    /* License Plate Badge (Khas Plat Nomor Indonesia) */
    .plate-badge {
        display: inline-flex;
        align-items: center;
        background-color: #18181b;
        color: #ffffff;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 700;
        font-size: 0.75rem;
        padding: 0.2rem 0.55rem;
        border-radius: 0.375rem;
        border: 1px solid #3f3f46;
        letter-spacing: 0.08em;
        box-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    /* Modern Card Hover Effects */
    .fi-section, .fi-wi-card, .fi-ta-content {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Modern Glassmorphism subtle overlay */
    .glass-card {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }
    .dark .glass-card {
        background: rgba(30, 41, 59, 0.85);
    }

    /* Table row highlight */
    .fi-ta-row:hover {
        background-color: rgba(99, 102, 241, 0.04) !important;
    }
    .dark .fi-ta-row:hover {
        background-color: rgba(99, 102, 241, 0.1) !important;
    }
</style>
