<section class="py-20 sm:py-28 bg-slate-950 text-white text-center relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900 to-slate-950 opacity-90 pointer-events-none"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 space-y-6">
        <span class="text-xs font-mono font-bold tracking-widest text-emerald-400 uppercase">
            THE FINAL CALL TO CREATION
        </span>
        <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-tight">
            Learn what exists. <br>
            <span class="text-purple-400">Discover what is possible.</span> <br>
            <span class="text-emerald-400">Create what comes next.</span>
        </h2>
        <p class="text-sm sm:text-base text-slate-400 max-w-2xl mx-auto leading-relaxed">
            Join an institution where young minds learn to question, invent, code, and lead. We are preparing students to build the world of tomorrow.
        </p>
        <div class="pt-4 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('register') }}" class="px-8 py-4 rounded-full font-black text-sm text-slate-950 bg-emerald-400 hover:bg-emerald-300 transition shadow-lg hover:shadow-emerald-500/20">
                Enroll Your Child Today
            </a>
            <a href="{{ route('courses.index') }}" class="px-7 py-4 rounded-full font-bold text-sm text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition">
                Explore Curriculum Handouts
            </a>
        </div>
    </div>
</section>

<!-- Script to handle interactive spark generator -->
<script>
    async function fetchNextSpark() {
        try {
            const res = await fetch('{{ route('motivation.spark') }}');
            const data = await res.json();
            if (data && data.quote) {
                document.getElementById('spark-quote').textContent = '"' + data.quote.quote + '"';
                document.getElementById('spark-author').innerHTML = '&mdash; ' + data.quote.author + ' &bull; Aim, Ascend, Achieve';
                document.getElementById('spark-fact').innerHTML = '<strong>Did you know?</strong> ' + data.fact.fact;
            }
        } catch(e) {
            console.log('Spark generation note:', e);
        }
    }
</script>
@endsection
