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
        color: #ffffff !important;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 700;
        font-size: 0.75rem;
        padding: 0.2rem 0.55rem;
        border-radius: 0.375rem;
        border: 1px solid #3f3f46;
        letter-spacing: 0.08em;
        box-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    /* Quick Action Cards */
    .qa-card {
        display: flex;
        flex-direction: column;
        padding: 1rem;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        text-decoration: none !important;
    }
    .dark .qa-card {
        background: #1e293b;
        border-color: #334155;
    }
    .qa-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px -2px rgba(0,0,0,0.08);
        border-color: #6366f1;
    }

    .qa-icon-indigo {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.6rem;
        background-color: #4f46e5;
        color: #ffffff !important;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(79, 70, 229, 0.25);
    }
    .qa-icon-amber {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.6rem;
        background-color: #f59e0b;
        color: #ffffff !important;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(245, 158, 11, 0.25);
    }
    .qa-icon-emerald {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.6rem;
        background-color: #10b981;
        color: #ffffff !important;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(16, 185, 129, 0.25);
    }
    .qa-icon-sky {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.6rem;
        background-color: #0284c7;
        color: #ffffff !important;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(2, 132, 199, 0.25);
    }
    .qa-icon-purple {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.6rem;
        background-color: #9333ea;
        color: #ffffff !important;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(147, 51, 234, 0.25);
    }

    /* Table row highlight */
    .fi-ta-row:hover {
        background-color: rgba(99, 102, 241, 0.04) !important;
    }
    .dark .fi-ta-row:hover {
        background-color: rgba(99, 102, 241, 0.1) !important;
    }
</style>
