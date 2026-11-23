<section id="admissions" class="py-16 sm:py-24 bg-white border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <div class="lg:col-span-5 space-y-5">
                <span class="text-xs font-extrabold uppercase tracking-widest text-purple-700 bg-purple-50 px-3 py-1 rounded-full border border-purple-200">
                    Admissions Now Open
                </span>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
                    Start Your Child's Journey of Discovery.
                </h2>
                <p class="text-sm text-slate-600 leading-relaxed">
                    Admissions for the upcoming academic session are conducted through personal discovery interviews and creative problem-solving assessments.
                </p>

                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2 text-xs">
                    <div class="font-bold text-slate-900">Admissions Criteria:</div>
                    <ul class="space-y-1 text-slate-600 list-disc list-inside">
                        <li>Genuine curiosity and willingness to learn</li>
                        <li>Respect for scientific inquiry and collaboration</li>
                        <li>Commitment to academic fundamentals</li>
                        <li>Non-conventional, creative thinking welcome</li>
                    </ul>
                </div>

                <div class="text-xs text-slate-500">
                    Official Admissions Desk: <strong class="text-slate-800">{{ $school['email'] ?: 'Not configured' }}</strong>
                </div>
            </div>

            <!-- Working Admissions / Contact Form -->
            <div class="lg:col-span-7">
                <div class="p-6 sm:p-8 rounded-3xl bg-slate-50 border border-slate-200 shadow-lg space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900">
                            Apply or Inquire for Admission
                        </h3>
                        <span class="text-[10px] font-mono text-purple-700 font-bold bg-purple-100 px-2 py-0.5 rounded-full">
                            Session 2026-2027
                        </span>
                    </div>

                    <form action="{{ route('contact') }}" method="POST" class="space-y-3.5">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Applicant / Parent Name *</label>
                                <input type="text" name="name" required placeholder="Full Name" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs text-slate-900 focus:ring-2 focus:ring-purple-500 focus:outline-hidden">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Email Address *</label>
                                <input type="email" name="email" required placeholder="email@address.com" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs text-slate-900 focus:ring-2 focus:ring-purple-500 focus:outline-hidden">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Contact Phone *</label>
                                <input type="tel" name="phone" required placeholder="+91 98765 43210" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs text-slate-900 focus:ring-2 focus:ring-purple-500 focus:outline-hidden">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Inquiry Track *</label>
                                <select name="inquiry_type" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs text-slate-900 focus:ring-2 focus:ring-purple-500 focus:outline-hidden">
                                    <option value="admissions">New Student Admission (Grades 1–12)</option>
                                    <option value="academics">Undergraduate &amp; Advanced Research</option>
                                    <option value="curriculum">Discovery &amp; Invention Curriculum</option>
                                    <option value="general">Campus Tour &amp; General Question</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Child's Interests, Aspirations &amp; Questions *</label>
                            <textarea name="message" required rows="3" placeholder="Tell us about what your child loves exploring, inventing, or questioning..." class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs text-slate-900 focus:ring-2 focus:ring-purple-500 focus:outline-hidden"></textarea>
                        </div>

                        <div class="pt-2 flex items-center justify-between">
                            <span class="text-[10px] text-slate-500">🔒 Information kept strictly confidential.</span>
                            <button type="submit" class="px-6 py-2.5 rounded-full font-bold text-xs text-white bg-purple-700 hover:bg-purple-800 transition shadow-sm">
                                Submit Admission Inquiry
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- ========================================================================= -->
<!-- 12. POWERFUL CLOSING MANIFESTO                                            -->
<!-- ========================================================================= -->
