@props([
    'articles' => [],
    'commandReference' => [],
    'activeSlug' => '',
    'macroCreateUrl' => '/admin/obs-macros/create',
    'isFilament' => false,
])

@php
    $firstSlug = $activeSlug !== '' ? $activeSlug : ($articles[0]['slug'] ?? '');
@endphp

<div class="obs-kb-shell" data-kb-root>
    <section class="obs-kb-hero" aria-labelledby="kb-hero-title">
        <div>
            <div class="obs-kb-kicker">Documentation &amp; Operator Guide</div>
            <h1 id="kb-hero-title" class="obs-kb-title">Obsisstant Knowledge Base</h1>
            <p class="obs-kb-subtitle">
                Learn how relay pairing, Control Deck telemetry, live Browser Source canvases, and Broadcast Macros fit together—plus search every built-in OBS WebSocket v5 command.
            </p>
        </div>

        <div style="display: flex; align-items: flex-end; gap: 0.85rem; flex-wrap: wrap;">
            <div class="obs-kb-search-box">
                <label for="kb-search" class="obs-kb-rail-label" style="padding: 0;">Search guides &amp; OBS requests</label>
                <input
                    id="kb-search"
                    type="search"
                    class="obs-kb-search-input"
                    placeholder="Search SetCurrentProgramScene, audio, overlay, pairing..."
                    autocomplete="off"
                >
            </div>
            <x-obs-mascot mode="badge" greeting="Need a tip? Tap me!" />
        </div>
    </section>

    <div class="obs-kb-grid">
        <aside class="obs-kb-sidebar" aria-label="Knowledge Base topics">
            <div class="obs-kb-rail-label">Operator Guides</div>
            @foreach ($articles as $article)
                <button
                    type="button"
                    class="obs-kb-nav-btn"
                    data-kb-topic-btn="{{ $article['slug'] }}"
                    aria-current="{{ $article['slug'] === $firstSlug ? 'true' : 'false' }}"
                >
                    <span>{{ $article['title'] }}</span>
                    <span class="obs-kb-nav-cat">{{ $article['category'] }}</span>
                </button>
            @endforeach

            <div class="obs-kb-rail-label" style="margin-block-start: 0.5rem;">Quick Jump</div>
            <a href="#obs-requests" class="obs-kb-nav-btn" id="kb-jump-ref">
                <span>OBS WebSocket v5 Reference</span>
                <span class="obs-kb-nav-cat">Macro request types &amp; payloads</span>
            </a>
        </aside>

        <div class="obs-kb-main">
            @foreach ($articles as $article)
                <article
                    class="obs-kb-article"
                    id="kb-article-{{ $article['slug'] }}"
                    data-kb-article="{{ $article['slug'] }}"
                    data-kb-search-text="{{ strtolower($article['title'].' '.$article['category'].' '.$article['summary'].' '.$article['markdown']) }}"
                    @if ($article['slug'] !== $firstSlug) hidden @endif
                >
                    <header class="obs-kb-article-head">
                        <div>
                            <span class="obs-kb-badge">{{ $article['category'] }}</span>
                            <h2 class="obs-kb-article-title">{{ $article['title'] }}</h2>
                            @if ($article['summary'] !== '')
                                <p class="obs-kb-article-summary">{{ $article['summary'] }}</p>
                            @endif
                        </div>
                    </header>

                    <div class="obs-kb-prose">
                        {!! $article['html'] !!}
                    </div>
                </article>
            @endforeach

            <div id="kb-articles-empty" class="obs-kb-article obs-kb-empty" hidden>
                No markdown guides matched your search—check the OBS Request Reference below or clear the search filter.
            </div>

            <section id="obs-requests" class="obs-kb-ref-card" aria-labelledby="obs-ref-heading">
                <div class="obs-kb-ref-head">
                    <div>
                        <span class="obs-kb-badge">Macro Builder Synced Catalog</span>
                        <h2 id="obs-ref-heading" class="obs-kb-article-title" style="margin-block-start: 0.375rem;">
                            OBS WebSocket v5 Request Reference
                        </h2>
                        <p class="obs-kb-article-summary">
                            Every action listed here is available in the searchable <strong>OBS Action / Request Type</strong> picker inside Create / Edit OBS Macro.
                        </p>
                    </div>
                    <a href="{{ $macroCreateUrl }}" class="obs-kb-action-link">Create OBS Macro</a>
                </div>

                <div tabindex="0" role="region" aria-labelledby="obs-ref-heading" class="obs-kb-table-wrap">
                    <table class="obs-kb-ref-table">
                        <thead>
                            <tr>
                                <th scope="col">Request Type</th>
                                <th scope="col">Category</th>
                                <th scope="col">Action &amp; Parameters</th>
                                <th scope="col">Example Payload</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($commandReference as $category => $commands)
                                @foreach ($commands as $cmd)
                                    @php
                                        $searchIndex = strtolower($cmd['request_type'].' '.$cmd['category'].' '.$cmd['label'].' '.$cmd['summary'].' '.implode(' ', $cmd['fields']));
                                    @endphp
                                    <tr data-kb-cmd-row data-kb-search-text="{{ $searchIndex }}">
                                        <th scope="row">
                                            <div class="obs-kb-cmd-cell">
                                                <code>{{ $cmd['request_type'] }}</code>
                                                <button
                                                    type="button"
                                                    class="obs-kb-copy-btn"
                                                    data-copy-text="{{ $cmd['request_type'] }}"
                                                    aria-label="Copy {{ $cmd['request_type'] }}"
                                                >
                                                    Copy
                                                </button>
                                            </div>
                                        </th>
                                        <td>{{ $category }}</td>
                                        <td>
                                            <div style="font-weight: 500; color: #FAF7EE;">{{ $cmd['label'] }}</div>
                                            <div style="color: #C8C0AF; margin-block-start: 0.125rem;">{{ $cmd['summary'] }}</div>
                                            @if ($cmd['notes'] !== '')
                                                <div style="color: #CDAD69; font-size: 0.75rem; margin-block-start: 0.25rem;">{{ $cmd['notes'] }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <code>{{ $cmd['example_json'] }}</code>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div id="kb-commands-empty" class="obs-kb-empty" hidden>
                    No OBS WebSocket v5 commands matched your search query.
                </div>
            </section>
        </div>
    </div>
</div>

<script>
    (() => {
        const root = document.querySelector('[data-kb-root]');
        if (!root) {
            return;
        }

        const searchInput = root.querySelector('#kb-search');
        const topicButtons = Array.from(root.querySelectorAll('[data-kb-topic-btn]'));
        const articles = Array.from(root.querySelectorAll('[data-kb-article]'));
        const commandRows = Array.from(root.querySelectorAll('[data-kb-cmd-row]'));
        const emptyArticles = root.querySelector('#kb-articles-empty');
        const emptyCommands = root.querySelector('#kb-commands-empty');

        let activeSlug = topicButtons.find((btn) => btn.getAttribute('aria-current') === 'true')?.getAttribute('data-kb-topic-btn')
            || articles[0]?.getAttribute('data-kb-article')
            || '';

        const setActiveTopic = (slug) => {
            activeSlug = slug;
            topicButtons.forEach((btn) => {
                btn.setAttribute('aria-current', btn.getAttribute('data-kb-topic-btn') === slug ? 'true' : 'false');
            });

            if (!searchInput || searchInput.value.trim() === '') {
                articles.forEach((article) => {
                    article.hidden = article.getAttribute('data-kb-article') !== slug;
                });
            }
        };

        topicButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const slug = button.getAttribute('data-kb-topic-btn') || '';
                if (searchInput && searchInput.value.trim() !== '') {
                    searchInput.value = '';
                    applyFilter('');
                }
                setActiveTopic(slug);
                const target = document.getElementById(`kb-article-${slug}`);
                const behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
                target?.scrollIntoView({ behavior, block: 'start' });
            });
        });

        const applyFilter = (queryRaw) => {
            const query = queryRaw.trim().toLowerCase();

            if (query === '') {
                if (emptyArticles) {
                    emptyArticles.hidden = true;
                }
                if (emptyCommands) {
                    emptyCommands.hidden = true;
                }
                articles.forEach((article) => {
                    article.hidden = article.getAttribute('data-kb-article') !== activeSlug;
                });
                commandRows.forEach((row) => {
                    row.hidden = false;
                });
                return;
            }

            let visibleArticles = 0;
            articles.forEach((article) => {
                const haystack = article.getAttribute('data-kb-search-text') || '';
                const matched = haystack.includes(query);
                article.hidden = !matched;
                if (matched) {
                    visibleArticles += 1;
                }
            });
            if (emptyArticles) {
                emptyArticles.hidden = visibleArticles > 0;
            }

            let visibleCommands = 0;
            commandRows.forEach((row) => {
                const haystack = row.getAttribute('data-kb-search-text') || '';
                const matched = haystack.includes(query);
                row.hidden = !matched;
                if (matched) {
                    visibleCommands += 1;
                }
            });
            if (emptyCommands) {
                emptyCommands.hidden = visibleCommands > 0;
            }
        };

        searchInput?.addEventListener('input', (event) => {
            applyFilter(event.target.value || '');
        });

        root.querySelectorAll('[data-copy-text]').forEach((button) => {
            button.addEventListener('click', async () => {
                const value = button.getAttribute('data-copy-text') || '';
                if (!value || !navigator.clipboard) {
                    return;
                }
                try {
                    await navigator.clipboard.writeText(value);
                    const original = button.textContent;
                    button.textContent = 'Copied';
                    setTimeout(() => {
                        button.textContent = original;
                    }, 1200);
                } catch {}
            });
        });
    })();
</script>
