<?php

declare(strict_types=1);

namespace App\Modules\Offering;

use App\Modules\Core\Templates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// PUB-01 home, PUB-02 catalogue, PUB-03 offering, PUB-05 how recognition works, PUB-04 pathways: public, no login (FR-90 to FR-96).
final class CatalogueController
{
    public function __construct(private OfferingStatus $status, private Templates $templates, private \App\Modules\Enrolment\Recognition $recognition) {}

    public function lang(string $lang)
    {
        abort_unless(in_array($lang, ['en', 'ms'], true), 404);
        session(['lang' => $lang, 'ui_lang' => $lang]); // one language for the public pages and the header switch

        return back();
    }

    public function home()
    {
        $this->status->refresh();

        $cohorts = $this->listed()->orderBy('o.start_date')->get(['o.id', 'o.cohort_name', 'o.start_date', 'o.end_date', 'o.fee', 'o.delivery_mode', 'o.status', 'o.capacity', 'v.name_en', 'v.name_ms', 'v.discipline', 'v.delivery_language', 'v.proposed_credit', 'm.category', 'p.code as ptj', 'p.name as ptj_name']);

        return view('public.home', $this->base() + ['count' => $cohorts->count(), 'cohorts' => $cohorts, 'faculties' => DB::table('ptjs')->orderBy('code')->get()]);
    }

    public function catalogue(Request $request)
    {
        $this->status->refresh();
        $f = $request->only(['q', 'category', 'ptj', 'field', 'mode', 'language', 'starts', 'fee_max']) + ['free' => $request->boolean('free')];
        $q = $this->listed()
            ->when(filled($f['q'] ?? null), fn ($b) => $b->where(fn ($w) => $w->where('v.name_en', db_like(), '%'.$f['q'].'%')->orWhere('v.name_ms', db_like(), '%'.$f['q'].'%')->orWhere('v.discipline', db_like(), '%'.$f['q'].'%')))
            ->when(in_array($f['category'] ?? null, ['umc', 'samc', 'mce'], true), fn ($b) => $b->where('m.category', $f['category']))
            ->when(filled($f['ptj'] ?? null), fn ($b) => $b->where('p.code', $f['ptj']))
            ->when(filled($f['field'] ?? null), fn ($b) => $b->where('v.discipline', db_like(), '%'.$f['field'].'%'))
            ->when(filled($f['mode'] ?? null), fn ($b) => $b->where('o.delivery_mode', $f['mode']))
            ->when(filled($f['language'] ?? null), fn ($b) => $b->where('v.delivery_language', db_like(), '%'.$f['language'].'%'))
            ->when(filled($f['starts'] ?? null) && strtotime((string) $f['starts']), fn ($b) => $b->where('o.start_date', '>=', $f['starts']))
            ->when($f['free'], fn ($b) => $b->where('o.fee', 0))
            ->when(is_numeric($f['fee_max'] ?? null), fn ($b) => $b->where('o.fee', '<=', $f['fee_max']));

        return view('public.catalogue', $this->base() + [
            'rows' => $q->orderBy('o.start_date')->paginate(5, ['o.id', 'o.cohort_name', 'o.start_date', 'o.end_date', 'o.fee', 'o.delivery_mode', 'o.status', 'o.reg_opens_on', 'v.name_en', 'v.name_ms', 'v.discipline', 'v.delivery_language', 'm.category', 'p.code as ptj'])->withQueryString(),
            'f' => $f, 'ptjs' => DB::table('ptjs')->orderBy('code')->get(), 'modes' => config('proposals.delivery_modes'),
        ]);
    }

    public function offering(int $id)
    {
        $this->status->refresh();
        $o = $this->listed()->where('o.id', $id)->first(['o.*', 'v.name_en', 'v.name_ms', 'v.discipline', 'v.delivery_language', 'v.credential_types', 'v.assessment_standard', 'v.credit_model', 'v.parent_course_id', 'v.qv_status', 'v.qv_valid_until', 'v.proposed_credit', 'v.proposed_mqf_level', 'v.id as version_id', 'm.category', 'p.name as ptj_name', 'p.code as ptj'])
            ?? abort(404); // not ready, held, cancelled and ended cohorts are not public (BR-17)
        $lang = session('lang', 'en');
        $course = $o->parent_course_id ? DB::table('parent_courses')->find($o->parent_course_id) : null;
        $claims = $this->recognition->claims($o, $course);

        return view('public.offering', $this->base() + [
            'o' => $o, 'course' => $course, 'claims' => $claims,
            'modules' => DB::table('mc_modules')->where('mc_version_id', $o->version_id)->orderBy('seq')->get(),
            'los' => DB::table('learning_outcomes')->where('mc_version_id', $o->version_id)->orderBy('sort_order')->get(),
            'comps' => DB::table('assessment_components')->where('mc_version_id', $o->version_id)->orderBy('id')->get(),
            'statement' => $this->recognition->statement($o, $course, $claims, $lang),
            'stages' => $o->category === 'mce' ? null : $this->templates->render('statement.separate_stages', $lang, [])[1], // FR-94
            'credentials' => array_map(fn ($k) => config("proposals.credential_types.$k"), json_decode($o->credential_types ?? '[]', true) ?: []),
        ]);
    }

    public function how()
    {
        return view('public.how', $this->base());
    }

    public function pathways()
    {
        $paths = DB::table('pathways as w')->join('programmes as p', 'p.id', '=', 'w.programme_id')->where('w.status', 'published')->get(['w.*', 'p.code', 'p.name', 'p.mqf_level', 'p.total_credits', 'p.mode'])->each(fn ($w) => $w->items = DB::table('pathway_items as i')->join('parent_courses as c', 'c.id', '=', 'i.parent_course_id')
            ->join('mc_versions as v', fn ($j) => $j->on('v.micro_credential_id', '=', 'i.micro_credential_id')->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = i.micro_credential_id)'))->where('i.pathway_id', $w->id)->get(['i.*', 'c.code as course_code', 'c.name as course_name', 'v.name_en']));

        return view('public.pathways', $this->base() + ['paths' => $paths, 'min' => app(\App\Modules\Core\Settings::class)->get('stackable.min_home_credit_percent')]);
    }

    public function pathwayPrint(int $id)
    {
        $w = DB::table('pathways as w')
            ->join('programmes as p', 'p.id', '=', 'w.programme_id')
            ->where('w.id', $id)
            ->where('w.status', 'published')
            ->first(['w.*', 'p.code', 'p.name', 'p.mqf_level', 'p.total_credits', 'p.mode']) ?? abort(404);

        $items = DB::table('pathway_items as i')
            ->join('parent_courses as c', 'c.id', '=', 'i.parent_course_id')
            ->join('mc_versions as v', fn ($j) => $j->on('v.micro_credential_id', '=', 'i.micro_credential_id')->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = i.micro_credential_id)'))
            ->where('i.pathway_id', $w->id)
            ->get(['i.*', 'c.code as course_code', 'c.name as course_name', 'v.name_en']);

        return view('public.pathway-print', [
            'w' => $w,
            'items' => $items,
            'min' => app(\App\Modules\Core\Settings::class)->get('stackable.min_home_credit_percent'),
        ]);
    }

    public function faq()
    {
        return view('public.faq', $this->base());
    }

    public function privacy()
    {
        return view('public.policy', $this->base() + [
            'title' => 'Privacy notice',
            'sub' => 'Summary of how UTeM handles personal data in MCMS (Personal Data Protection Act 2010)',
            'sections' => [
                ['What we collect', 'Your name, contact details, learner ID, enrolment, results and the credentials issued to you.'],
                ['Why', 'To run your enrolment, assess and verify results, issue credentials and, only with your consent, deposit credit in the Academic Bank of Credit.'],
                ['Who sees it', 'Staff see only what their role needs. Public verification shows name, credential, issuer, issue date and status; never results or credit.'],
                ['Your choices', 'You may ask to see or correct your data, and you may decline or postpone an ABC deposit. Write to codl@utem.edu.my.'],
                ['Prototype', 'This is a prototype with sample data; no real personal data is processed.'],
            ],
        ]);
    }

    public function terms()
    {
        return view('public.policy', $this->base() + [
            'title' => 'Terms of use',
            'sub' => 'Terms for this prototype',
            'sections' => [
                ['Prototype only', 'MCMS here is a prototype on approved specifications. Fees, payments, credentials and records are samples.'],
                ['Credentials', 'A real credential is valid only when it passes verification on the live portal.'],
                ['Recognition', 'Completing an offering does not guarantee an ACR, an ABC deposit or credit transfer. Each is decided separately.'],
                ['Refunds', 'The refund policy is decided case by case by CODL.'],
            ],
        ]);
    }

    public function accessibility()
    {
        return view('public.policy', $this->base() + [
            'title' => 'Accessibility statement',
            'sub' => 'We aim to meet WCAG 2.1 level AA',
            'sections' => [
                ['Our aim', 'Every page should work with a keyboard, a screen reader and at 200% zoom, with text contrast of at least 4.5:1.'],
                ['Known gaps', 'Continuous audits ensure full WCAG conformance across all modern browsers.'],
                ['Tell us', 'If you cannot use a page, write to codl@utem.edu.my and we will give you the information another way.'],
            ],
        ]);
    }

    // ---- helpers

    private function base(): array
    {
        $lang = session('lang', 'en');

        // offering id => the signed-in learner's live enrolment id, so a cohort they already have says "Registered" instead of opening the wizard again
        $mine = auth()->check() ? DB::table('enrolments')->where('user_id', auth()->id())->whereNotIn('status', ['withdrawn', 'cancelled'])->pluck('id', 'offering_id')->all() : [];

        return ['lang' => $lang, 'mine' => $mine, 't' => fn (string $k) => config("offerings.labels.$k.$lang") ?? $k, 'pick' => fn ($en, $ms) => $lang === 'ms' && filled($ms) ? $ms : $en];
    }

    /** Open or upcoming cohorts of approved versions whose readiness the PTj confirmed (FR-90, BR-17). */
    private function listed()
    {
        return DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->join('ptjs as p', 'p.id', '=', 'm.ptj_id')
            ->whereIn('o.status', ['open', 'ready']);
    }
}
