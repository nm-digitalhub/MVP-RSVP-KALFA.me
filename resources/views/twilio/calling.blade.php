<x-layouts.enterprise-app>
    <x-slot:title>שיחות RSVP — Twilio</x-slot:title>

<div class="no-main-spacing min-h-screen bg-surface py-8 px-4">
    <style>
        .calling-card { background: #1e293b; border-radius: 16px; padding: 2.5rem; width: 100%; max-width: 440px; box-shadow: 0 25px 50px rgba(0,0,0,.4); margin: 2rem auto; }
        .calling-card h1 { font-size: 1.5rem; margin-bottom: .5rem; color: #f8fafc; }
        .calling-card .subtitle { color: #94a3b8; font-size: .9rem; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        .calling-card label { display: block; font-size: .85rem; color: #94a3b8; margin-bottom: .5rem; font-weight: 500;}
        .calling-card input[type="text"], .calling-card input[type="email"] { width: 100%; padding: .85rem 1rem; border: 1px solid #334155; border-radius: 10px; background: #0f172a; color: #f8fafc; font-size: 1rem; outline: none; transition: border-color .2s; }
        .calling-card input.ltr { direction: ltr; text-align: left; font-size: 1.1rem;}
        .calling-card input:focus { border-color: #6366f1; }
        .calling-card .btn-primary { width: 100%; margin-top: .5rem; padding: .85rem; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; font-size: 1rem; font-weight: 600; border: none; border-radius: 10px; cursor: pointer; transition: opacity .2s; }
        .calling-card .btn-primary:hover { opacity: .9; }
        .calling-card .msg { margin-bottom: 1.5rem; padding: .85rem 1rem; border-radius: 10px; font-size: .9rem; line-height: 1.5; }
        .calling-card .msg.success { background: #064e3b; color: #6ee7b7; border: 1px solid #047857;}
        .calling-card .msg.error   { background: #7f1d1d; color: #fca5a5; border: 1px solid #b91c1c;}
        .extended-form { background: #0f172a; border-radius: 12px; padding: 1.5rem; border: 1px dashed #475569; margin-bottom: 1.5rem;}
        .extended-form h3 { font-size: 1.1rem; color: #f8fafc; margin-bottom: 1rem;}
        .terminal-log { margin-top: 1.5rem; background:#020617; border-radius:12px; padding:1rem; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size:0.8rem; color:#e5e7eb; border:1px solid #1e293b; }
        .terminal-log-title { margin-bottom:0.5rem; color:#38bdf8; }
        .terminal-log-pre { white-space:pre-wrap; word-break:break-word; max-height:220px; overflow:auto; }
        .terminal-log-wrap { display: none; }
        .terminal-log-wrap.visible { display: block; }
        .calling-card button:disabled { opacity: 0.7; cursor: not-allowed; }
    </style>

    <div class="calling-card">
        <h1 class="flex items-center gap-2">
            <x-heroicon-o-phone class="w-7 h-7 shrink-0 text-slate-300" />
            שיחות RSVP
        </h1>
        <p class="subtitle">הזן מספר טלפון של אורח כדי להתקשר ולבקש אישור הגעה</p>

        <div id="call-msg" class="msg" role="alert" style="display:none;"></div>

        <form id="calling-form" method="post" action="{{ route('twilio.calling.initiate') }}">
            @csrf
            <div class="form-group">
                <label for="number">מספר טלפון לאישור הגעה</label>
                <input type="text" id="number" name="number" class="ltr" placeholder="050-1234567 או +972501234567" value="{{ $searchedPhone }}" {{ $showNewGuestForm ? 'readonly' : 'required autofocus' }}>
            </div>

            @if ($showNewGuestForm)
                <div class="extended-form">
                    <h3 class="flex items-center gap-2">
                        <x-heroicon-o-sparkles class="w-5 h-5 text-amber-400" />
                        יצירת אורח חדש
                    </h3>
                    <input type="hidden" name="action" value="create_guest">

                    <div class="form-group">
                        <label for="name">שם מלא (חובה)</label>
                        <input type="text" id="name" name="name" placeholder="לדוגמה: ישראל ישראלי" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="email">אימייל (אופציונלי)</label>
                        <input type="email" id="email" name="email" class="ltr" placeholder="example@email.com">
                    </div>

                    <div class="form-group">
                        <label for="group_name">קבוצה / משפחה (אופציונלי)</label>
                        <input type="text" id="group_name" name="group_name" placeholder="לדוגמה: משפחת כהן">
                    </div>
                </div>
                <button type="submit" id="submit-btn" class="btn-primary inline-flex items-center justify-center gap-2">
                    <x-heroicon-o-user-plus class="w-5 h-5" />
                    הוסף אורח וחייג עכשיו
                </button>
                <button type="button" onclick="window.location.href='{{ route('twilio.calling.index') }}'" class="mt-2.5 w-full py-3.5 rounded-xl border border-slate-600 text-slate-400 hover:bg-slate-800/50 transition-colors inline-flex items-center justify-center gap-2">
                    <x-heroicon-o-arrow-left class="w-5 h-5" />
                    ביטול חיפוש
                </button>
            @else
                <button type="submit" id="submit-btn" class="btn-primary inline-flex items-center justify-center gap-2">
                    <x-heroicon-o-phone class="w-5 h-5" />
                    חייג עכשיו
                </button>
            @endif
        </form>

        <div id="terminal-log-wrap" class="terminal-log terminal-log-wrap">
            <div class="terminal-log-title inline-flex items-center gap-2">
                <x-heroicon-o-command-line class="w-4 h-4 shrink-0" />
                TERMINAL LOG <span id="terminal-status"></span>
            </div>
            <pre id="terminal-log-pre" class="terminal-log-pre"></pre>
            <div id="call-log-wrap" class="terminal-log-wrap" style="margin-top:1rem; border-top:1px solid #1e293b; padding-top:0.75rem;">
                <div class="terminal-log-title inline-flex items-center gap-2">
                    <x-heroicon-o-chat-bubble-left-right class="w-4 h-4 shrink-0" />
                    לוג שיחה <span id="call-log-status"></span>
                </div>
                <pre id="call-log-pre" class="terminal-log-pre" style="max-height:180px;"></pre>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
(function() {
    const form = document.getElementById('calling-form');
    const callMsg = document.getElementById('call-msg');
    const terminalWrap = document.getElementById('terminal-log-wrap');
    const terminalPre = document.getElementById('terminal-log-pre');
    const terminalStatus = document.getElementById('terminal-status');
    const submitBtn = document.getElementById('submit-btn');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (submitBtn) submitBtn.disabled = true;
        terminalWrap.classList.add('visible');
        terminalPre.textContent = '';
        terminalStatus.textContent = '… מתחבר';
        callMsg.style.display = 'none';

        const formData = new FormData(form);
        const url = form.action + (form.action.includes('?') ? '&' : '?') + 'stream=1';
        let buffer = '';

        try {
            const res = await fetch(url, { method: 'POST', body: formData });
            if (!res.ok) throw new Error('Network ' + res.status);
            if (!res.body) throw new Error('No stream');
            const reader = res.body.getReader();
            const dec = new TextDecoder();
            while (true) {
                const { value, done } = await reader.read();
                if (done) break;
                buffer += dec.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';
                for (const line of lines) {
                    const t = line.trim();
                    if (!t) continue;
                    try {
                        const data = JSON.parse(t);
                        if (data.type === 'log') {
                            terminalPre.textContent += (terminalPre.textContent ? '\n' : '') + '$ ' + data.msg;
                            terminalPre.scrollTop = terminalPre.scrollHeight;
                            if (terminalStatus.textContent === '… מתחבר') terminalStatus.textContent = '';
                        } else if (data.type === 'done') {
                            terminalStatus.textContent = data.success ? 'השיחה יצאה' : '✗ שגיאה';
                            callMsg.innerHTML = data.message || '';
                            callMsg.className = 'msg ' + (data.messageType || (data.success ? 'success' : 'error'));
                            callMsg.style.display = data.message ? 'block' : 'none';
                            if (data.showNewGuestForm) window.location.href = '{{ route("twilio.calling.index") }}?show_new_guest=1&number=' + encodeURIComponent(data.searchedPhone || '');
                            if (data.callSid) startCallLogPoll(data.callSid);
                        }
                    } catch (_) {}
                }
            }
        } catch (err) {
            terminalStatus.textContent = '✗ שגיאה';
            terminalPre.textContent += (terminalPre.textContent ? '\n' : '') + '$ Error: ' + err.message;
            callMsg.textContent = 'שגיאה: ' + err.message;
            callMsg.className = 'msg error';
            callMsg.style.display = 'block';
        }
        if (submitBtn) submitBtn.disabled = false;
    });

    const callLogStatusEl = document.getElementById('call-log-status');
    const callLogPreEl = document.getElementById('call-log-pre');
    const callLogWrapEl = document.getElementById('call-log-wrap');
    let callLogPollTimer = null;

    function statusLabel(s) {
        if (!s) return '';
        var map = { 'queued': 'בתור', 'ringing': 'מצלצל', 'in-progress': 'נענתה', 'completed': 'הושלמה', 'busy': 'תפוס', 'failed': 'נכשל', 'no-answer': 'לא נענה', 'canceled': 'בוטל' };
        return map[s] || s;
    }

    function startCallLogPoll(callSid) {
        if (callLogWrapEl) callLogWrapEl.classList.add('visible');
        if (callLogStatusEl) callLogStatusEl.textContent = '… טוען';
        function poll() {
            fetch('{{ route("twilio.calling.logs") }}?call_sid=' + encodeURIComponent(callSid))
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (callLogStatusEl) callLogStatusEl.textContent = d.status ? statusLabel(d.status) : '';
                    var lines = d.lines || [];
                    if (lines.length > 0 || d.status) {
                        var parts = [];
                        if (d.status) parts.push('סטטוס: ' + statusLabel(d.status));
                        lines.forEach(function(l) {
                            parts.push((l.role === 'user' ? 'אורח: ' : 'בוט: ') + (l.text || '').trim());
                        });
                        if (callLogPreEl) callLogPreEl.textContent = parts.join('\n');
                        callLogPreEl.scrollTop = callLogPreEl.scrollHeight;
                    }
                    if (d.status === 'completed' || d.status === 'failed' || d.status === 'busy' || d.status === 'no-answer' || d.status === 'canceled') {
                        if (terminalStatus) terminalStatus.textContent = statusLabel(d.status);
                        if (callLogPollTimer) clearInterval(callLogPollTimer);
                        callLogPollTimer = null;
                        return;
                    }
                })
                .catch(function() {});
        }
        poll();
        callLogPollTimer = setInterval(poll, 2000);
    }
})();
    </script>
    @endpush
</div>
</x-layouts.enterprise-app>
