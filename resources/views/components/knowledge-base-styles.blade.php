<style>
    .obs-kb-shell {
        display: grid;
        gap: 1.5rem;
        font-family: 'Poppins', ui-sans-serif, system-ui, -apple-system, sans-serif;
    }

    .obs-kb-hero {
        background: linear-gradient(165deg, rgba(24, 22, 18, 0.76) 0%, rgba(12, 11, 9, 0.86) 100%);
        -webkit-backdrop-filter: blur(18px) saturate(145%);
        backdrop-filter: blur(18px) saturate(145%);
        border: 1px solid rgba(205, 173, 105, 0.26);
        border-radius: 1rem;
        padding: clamp(1.25rem, 2.5vw, 1.75rem);
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.16), 0 20px 44px -24px rgba(0, 0, 0, 0.88);
    }

    .obs-kb-kicker {
        font-size: 0.75rem;
        font-weight: 500;
        color: #CDAD69;
        margin-block-end: 0.25rem;
    }

    .obs-kb-title {
        margin: 0;
        font-size: clamp(1.5rem, 1.15rem + 1.2vw, 2.25rem);
        font-weight: 300;
        line-height: 1.15;
        letter-spacing: -0.02em;
        color: #FAF7EE;
    }

    .obs-kb-subtitle {
        margin: 0.375rem 0 0;
        max-inline-size: 62ch;
        font-size: 0.875rem;
        color: #C8C0AF;
    }

    .obs-kb-search-box {
        inline-size: min(100%, 22rem);
        display: grid;
        gap: 0.375rem;
    }

    .obs-kb-search-input {
        inline-size: 100%;
        min-block-size: 2.5rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(205, 173, 105, 0.32);
        background-color: #0B0B0A;
        color: #FAF7EE;
        font: inherit;
        font-size: 0.875rem;
    }

    .obs-kb-search-input::placeholder {
        color: #8E8777;
    }

    .obs-kb-search-input:focus-visible {
        outline: 2px solid #F4E185;
        outline-offset: 2px;
    }

    .obs-kb-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1.5rem;
        align-items: start;
    }

    @media (min-width: 60rem) {
        .obs-kb-grid {
            grid-template-columns: 17.5rem minmax(0, 1fr);
        }

        .obs-kb-sidebar {
            position: sticky;
            inset-block-start: 4.75rem;
        }
    }

    .obs-kb-sidebar {
        background: rgba(18, 17, 14, 0.74);
        -webkit-backdrop-filter: blur(18px) saturate(145%);
        backdrop-filter: blur(18px) saturate(145%);
        border: 1px solid rgba(205, 173, 105, 0.22);
        border-radius: 1rem;
        padding: 0.75rem;
        display: grid;
        gap: 0.375rem;
        box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.14), 0 18px 40px -24px rgba(0, 0, 0, 0.85);
    }

    .obs-kb-rail-label {
        padding: 0.25rem 0.625rem;
        font-size: 0.75rem;
        font-weight: 500;
        color: #8E8777;
    }

    .obs-kb-nav-btn {
        display: grid;
        gap: 0.125rem;
        inline-size: 100%;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid transparent;
        background: transparent;
        color: #C8C0AF;
        font: inherit;
        font-size: 0.8125rem;
        font-weight: 500;
        text-align: start;
        text-decoration: none;
        cursor: pointer;
        transition: background-color 140ms cubic-bezier(0.22, 1, 0.36, 1), border-color 140ms cubic-bezier(0.22, 1, 0.36, 1), color 140ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .obs-kb-nav-btn:hover {
        background-color: #1B1A17;
        color: #FAF7EE;
    }

    .obs-kb-nav-btn[aria-current="true"] {
        background-color: #1B1A17;
        border-color: rgba(205, 173, 105, 0.34);
        box-shadow: inset 3px 0 0 #CDAD69;
        color: #F4E185;
    }

    .obs-kb-nav-cat {
        font-size: 0.6875rem;
        font-weight: 400;
        color: #8E8777;
    }

    .obs-kb-main {
        display: grid;
        gap: 1.5rem;
        min-inline-size: 0;
    }

    .obs-kb-article {
        background: rgba(18, 17, 14, 0.74);
        -webkit-backdrop-filter: blur(18px) saturate(145%);
        backdrop-filter: blur(18px) saturate(145%);
        border: 1px solid rgba(205, 173, 105, 0.22);
        border-radius: 1rem;
        padding: clamp(1.25rem, 2.5vw, 2rem);
        display: grid;
        gap: 1rem;
        box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.14), 0 18px 40px -24px rgba(0, 0, 0, 0.85);
    }

    .obs-kb-article[hidden] {
        display: none;
    }

    .obs-kb-article-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        padding-block-end: 1rem;
        border-block-end: 1px solid rgba(205, 173, 105, 0.14);
    }

    .obs-kb-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.1875rem 0.5rem;
        border-radius: 0.375rem;
        border: 1px solid rgba(205, 173, 105, 0.28);
        background-color: #1B1A17;
        font-size: 0.75rem;
        font-weight: 500;
        color: #F4E185;
    }

    .obs-kb-article-title {
        margin: 0.375rem 0 0;
        font-size: clamp(1.25rem, 1.05rem + 0.65vw, 1.625rem);
        font-weight: 500;
        line-height: 1.25;
        color: #FAF7EE;
    }

    .obs-kb-article-summary {
        margin: 0.375rem 0 0;
        font-size: 0.875rem;
        color: #C8C0AF;
    }

    /* Rendered Markdown Prose */
    .obs-kb-prose {
        color: #D8D0C0;
        font-size: 0.9375rem;
        line-height: 1.65;
        display: grid;
        gap: 0.875rem;
        min-inline-size: 0;
    }

    .obs-kb-prose h2 {
        margin: 0.75rem 0 0;
        font-size: 1.125rem;
        font-weight: 500;
        color: #FAF7EE;
    }

    .obs-kb-prose h3 {
        margin: 0.5rem 0 0;
        font-size: 0.9375rem;
        font-weight: 600;
        color: #F4E185;
    }

    .obs-kb-prose p,
    .obs-kb-prose ul,
    .obs-kb-prose ol {
        margin: 0;
        max-inline-size: 68ch;
    }

    .obs-kb-prose ul,
    .obs-kb-prose ol {
        padding-inline-start: 1.25rem;
        display: grid;
        gap: 0.375rem;
    }

    .obs-kb-prose hr {
        border: 0;
        border-block-start: 1px solid rgba(205, 173, 105, 0.14);
        margin: 0.5rem 0;
    }

    .obs-kb-prose code {
        padding: 0.125rem 0.375rem;
        border-radius: 0.375rem;
        background-color: #1B1A17;
        border: 1px solid rgba(205, 173, 105, 0.18);
        color: #F4E185;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.8125rem;
    }

    .obs-kb-prose pre {
        margin: 0;
        padding: 0.875rem 1rem;
        border-radius: 0.75rem;
        background-color: #0B0B0A;
        border: 1px solid rgba(205, 173, 105, 0.22);
        overflow-x: auto;
    }

    .obs-kb-prose pre code {
        padding: 0;
        border: 0;
        background: transparent;
        color: #FAF7EE;
    }

    .obs-kb-prose table {
        inline-size: 100%;
        border-collapse: collapse;
        font-size: 0.8125rem;
        background-color: #0B0B0A;
        border: 1px solid rgba(205, 173, 105, 0.18);
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .obs-kb-prose th,
    .obs-kb-prose td {
        padding: 0.625rem 0.75rem;
        border-block-end: 1px solid rgba(205, 173, 105, 0.12);
        text-align: start;
        vertical-align: top;
    }

    .obs-kb-prose thead th {
        background-color: #1B1A17;
        color: #FAF7EE;
        font-weight: 500;
    }

    /* Responsive OBS WebSocket v5 Command Reference Table (container query stacked on mobile) */
    .obs-kb-ref-card {
        background-color: #121210;
        border: 1px solid rgba(205, 173, 105, 0.22);
        border-radius: 1rem;
        padding: clamp(1.25rem, 2.5vw, 1.75rem);
        display: grid;
        gap: 1rem;
    }

    .obs-kb-ref-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .obs-kb-action-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-block-size: 2.25rem;
        padding: 0.375rem 0.875rem;
        border-radius: 0.5rem;
        background-image: linear-gradient(180deg, #DFC27D 0%, #CDAD69 100%);
        color: #070706;
        font-size: 0.8125rem;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid rgba(244, 225, 133, 0.45);
    }

    .obs-kb-action-link:hover {
        background-image: linear-gradient(180deg, #E8CE8D 0%, #D6B773 100%);
    }

    .obs-kb-table-wrap {
        overflow: auto;
        max-inline-size: 100%;
        max-block-size: min(44rem, 75dvh);
        container-type: inline-size;
        border-radius: 0.75rem;
        border: 1px solid rgba(205, 173, 105, 0.16);
    }

    .obs-kb-ref-table {
        inline-size: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.8125rem;
        text-align: start;
        background-color: #0B0B0A;
        --label-1: "Request Type";
        --label-2: "Category";
        --label-3: "Purpose";
        --label-4: "Example Payload";
    }

    .obs-kb-ref-table thead {
        position: sticky;
        inset-block-start: 0;
        z-index: 2;
    }

    .obs-kb-ref-table thead th {
        background-color: #1B1A17;
        color: #FAF7EE;
        font-size: 0.75rem;
        font-weight: 500;
        padding: 0.625rem 0.75rem;
        border-block-end: 1px solid rgba(205, 173, 105, 0.22);
        text-align: start;
        white-space: nowrap;
    }

    .obs-kb-ref-table tbody th,
    .obs-kb-ref-table tbody td {
        padding: 0.75rem;
        border-block-end: 1px solid rgba(205, 173, 105, 0.12);
        vertical-align: top;
        text-align: start;
    }

    .obs-kb-ref-table tbody th {
        position: sticky;
        inset-inline-start: 0;
        z-index: 1;
        background-color: #0B0B0A;
        font-weight: 500;
        white-space: nowrap;
    }

    .obs-kb-ref-table tbody tr[hidden] {
        display: none;
    }

    .obs-kb-cmd-cell {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .obs-kb-copy-btn {
        padding: 0.1875rem 0.5rem;
        border-radius: 0.375rem;
        border: 1px solid rgba(205, 173, 105, 0.26);
        background-color: #1B1A17;
        color: #C8C0AF;
        font: inherit;
        font-size: 0.6875rem;
        font-weight: 500;
        cursor: pointer;
    }

    .obs-kb-copy-btn:hover {
        border-color: #CDAD69;
        color: #F4E185;
    }

    .obs-kb-empty {
        padding: 1.5rem;
        text-align: center;
        color: #8E8777;
        font-size: 0.875rem;
    }

    @container (width < 600px) {
        .obs-kb-ref-table,
        .obs-kb-ref-table thead,
        .obs-kb-ref-table tbody,
        .obs-kb-ref-table tr,
        .obs-kb-ref-table th,
        .obs-kb-ref-table td {
            display: block;
        }

        .obs-kb-ref-table thead {
            display: none;
        }

        .obs-kb-ref-table tr {
            margin-block-end: 0.75rem;
            border: 1px solid rgba(205, 173, 105, 0.2);
            border-radius: 0.5rem;
            background-color: #121210;
        }

        .obs-kb-ref-table tbody th {
            position: sticky;
            inset-block-start: 0;
            z-index: 2;
            background-color: #1B1A17;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .obs-kb-ref-table tbody td:nth-child(2)::before {
            content: var(--label-2) ": ";
            color: #8E8777;
            font-weight: 500;
        }

        .obs-kb-ref-table tbody td:nth-child(3)::before {
            content: var(--label-3) ": ";
            color: #8E8777;
            font-weight: 500;
        }

        .obs-kb-ref-table tbody td:nth-child(4)::before {
            content: var(--label-4) ": ";
            color: #8E8777;
            font-weight: 500;
        }
    }

    @media (forced-colors: active) {
        .obs-kb-hero,
        .obs-kb-sidebar,
        .obs-kb-article,
        .obs-kb-ref-card,
        .obs-kb-table-wrap,
        .obs-kb-badge {
            background: Canvas;
            color: CanvasText;
            border: 1px solid CanvasText;
        }

        .obs-kb-nav-btn[aria-current="true"],
        .obs-kb-action-link {
            background: Highlight;
            color: HighlightText;
            border: 1px solid ButtonText;
        }
    }
</style>
