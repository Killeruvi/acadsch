@extends('install.layout')

@section('title', 'Database Connection & Schema')

@section('content')
<div>
    <div class="mb-6">
        <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">2. Database Configuration</h2>
        <p class="text-sm text-slate-400 mt-1">
            Specify your MySQL / MariaDB database credentials. The installer will test connection and automatically execute migrations.
        </p>
    </div>

    <form method="POST" action="{{ route('install.database.post') }}" class="space-y-5">
        @csrf

        <!-- Connection Driver -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">Database Engine</label>
            <div class="grid grid-cols-2 gap-4">
                <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-800 bg-slate-900/80 cursor-pointer hover:border-purple-500/50 transition">
                    <input type="radio" name="db_connection" value="mysql" id="driver_mysql" {{ old('db_connection', $currentDb['connection']) === 'mysql' ? 'checked' : '' }} class="text-purple-600 focus:ring-purple-500" onchange="toggleDbFields()">
                    <div>
                        <div class="text-xs font-bold text-white">MySQL / MariaDB</div>
                        <div class="text-[11px] text-slate-400">Standard for cPanel & VPS</div>
                    </div>
                </label>
                <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-800 bg-slate-900/80 cursor-pointer hover:border-purple-500/50 transition">
                    <input type="radio" name="db_connection" value="sqlite" id="driver_sqlite" {{ old('db_connection', $currentDb['connection']) === 'sqlite' ? 'checked' : '' }} class="text-purple-600 focus:ring-purple-500" onchange="toggleDbFields()">
                    <div>
                        <div class="text-xs font-bold text-white">SQLite</div>
                        <div class="text-[11px] text-slate-400">Single-file local database</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- MySQL Specific Settings -->
        <div id="mysql_fields" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Database Host</label>
                    <input type="text" name="db_host" value="{{ old('db_host', $currentDb['host'] ?: '127.0.0.1') }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Database Port</label>
                    <input type="text" name="db_port" value="{{ old('db_port', $currentDb['port'] ?: '3306') }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Database Username</label>
                    <input type="text" name="db_username" value="{{ old('db_username', $currentDb['username'] ?: 'root') }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Database Password</label>
                    <input type="password" name="db_password" value="{{ old('db_password', $currentDb['password']) }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="••••••••••••">
                </div>
            </div>
        </div>

        <!-- Database Name -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5" id="dbname_label">Database Name</label>
            <input type="text" name="db_database" id="db_database" value="{{ old('db_database', $currentDb['database']) }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none font-mono" placeholder="school_erp">
            <p class="text-[11px] text-slate-500 mt-1" id="dbname_help">Create the database in cPanel / phpMyAdmin first, then provide its name here.</p>
        </div>

        <div class="p-3.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-xs text-indigo-300 flex items-start gap-2.5">
            <svg class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                On submission, credentials will be tested, written to <code class="font-mono text-white">.env</code>, and database tables will be migrated automatically.
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-800">
            <a href="{{ route('install.requirements') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white bg-slate-900 hover:bg-slate-800 transition">
                &larr; Back to Prerequisites
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-extrabold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-lg shadow-purple-500/25 transition">
                Connect & Migrate Schema &rarr;
            </button>
        </div>
    </form>
</div>

<script>
    function toggleDbFields() {
        const isSqlite = document.getElementById('driver_sqlite').checked;
        const mysqlFields = document.getElementById('mysql_fields');
        const dbnameLabel = document.getElementById('dbname_label');
        const dbnameHelp = document.getElementById('dbname_help');
        const dbInput = document.getElementById('db_database');

        if (isSqlite) {
            mysqlFields.style.display = 'none';
            dbnameLabel.textContent = 'SQLite Database File Path';
            dbnameHelp.textContent = 'Absolute or relative path to .sqlite file (e.g. database/database.sqlite).';
            if (!dbInput.value || dbInput.value === 'school_erp') {
                dbInput.value = 'database/database.sqlite';
            }
        } else {
            mysqlFields.style.display = 'block';
            dbnameLabel.textContent = 'Database Name';
            dbnameHelp.textContent = 'Create the database in cPanel / phpMyAdmin first, then provide its name here.';
        }
    }
    toggleDbFields();
</script>
@endsection
