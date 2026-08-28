<?php
/**
 * Plugin Name: Darmbulanz Survey
 * Description: Frontend registration intake for the Darmbulanz professional network (Fachkreise), with admin review and an optional sync to social.darmbulanz.net.
 * Version: 1.0.0
 * Requires PHP: 8.2
 * Author: Darmbulanz
 * Text Domain: darmbulanz-survey
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Darmbulanz_Survey {
    private const SHORTCODE = 'darmbulanz_survey';
    private const VERSION = '1.0.0';
    private const DEFAULT_LANGUAGE = 'de';
    private const LANGUAGE_COOKIE = 'db_lang';
    private const POST_TYPE = 'db_application';
    private const AJAX_ACTION = 'db_submit_application';
    private const DOWNLOAD_ACTION = 'db_download_document';
    private const NONCE_ACTION = 'darmbulanz_registration';
    private const META_STATUS = '_db_application_status';
    private const META_PREFIX = '_db_application_';
    private const PLATFORM_OPTION = 'db_platform_settings';
    private const PRIVACY_POLICY_OPTION = 'db_privacy_policy_url';
    private const REST_NAMESPACE = 'darmbulanz/v1';
    private const MANAGE_CAPABILITY = 'db_manage_applications';
    private const DOCUMENT_UPLOAD_SUBDIR = 'darmbulanz-uploads';
    private const PER_PAGE_OPTIONS = [20, 30, 50, 100];
    private const DEFAULT_PER_PAGE = 30;
    private const PER_PAGE_USER_META = 'db_applications_per_page';
    private const REJECTION_EMAIL_FROM = 'noreply@darmbulanz.net';
    private const MAX_UPLOAD_FILES = 5;

    private array $translations = [];
    private ?string $current_language = null;

    private const MAIN_CATEGORIES = [
        'facharztrichtung' => 'Facharztrichtung',
        'ernahrungsberatung' => 'Ernährungsberatung',
        'heilberuf' => 'Heilberuf',
        'therapeut' => 'Therapeut',
        'psychologie-und-psychiatrie' => 'Psychologie und Psychiatrie',
        'osteopathie' => 'Osteopathie',
        'klinik-und-institution' => 'Klinik und Institution',
        'wissenschaft' => 'Wissenschaft',
    ];

    private const SPECIALTIES_BY_CATEGORY = [
        'facharztrichtung' => [
            'allgemeinmedizin' => 'Allgemeinmedizin',
            'anasthesiologie' => 'Anästhesiologie',
            'arbeitsmedizin' => 'Arbeitsmedizin',
            'augenheilkunde' => 'Augenheilkunde',
            'chirurgie-allgemein-und-viszeralchirurgie' => 'Chirurgie (Allgemein- und Viszeralchirurgie)',
            'gefaschirurgie' => 'Gefäßchirurgie',
            'herzchirurgie' => 'Herzchirurgie',
            'kinderchirurgie' => 'Kinderchirurgie',
            'orthopadie-und-unfallchirurgie' => 'Orthopädie und Unfallchirurgie',
            'plastische-rekonstruktive-und-asthetische-chirurgie' => 'Plastische, Rekonstruktive und Ästhetische Chirurgie',
            'thoraxchirurgie' => 'Thoraxchirurgie',
            'frauenheilkunde-und-geburtshilfe' => 'Frauenheilkunde und Geburtshilfe',
            'hals-nasen-ohrenheilkunde' => 'Hals-Nasen-Ohrenheilkunde',
            'haut-und-geschlechtskrankheiten-dermatologie' => 'Haut- und Geschlechtskrankheiten (Dermatologie)',
            'humangenetik' => 'Humangenetik',
            'hygiene-und-umweltmedizin' => 'Hygiene und Umweltmedizin',
            'innere-medizin-und-angiologie' => 'Innere Medizin und Angiologie',
            'innere-medizin-endokrinologie-und-diabetologie' => 'Innere Medizin, Endokrinologie und Diabetologie',
            'innere-medizin-und-gastroenterologie' => 'Innere Medizin und Gastroenterologie',
            'innere-medizin-hamatologie-und-onkologie' => 'Innere Medizin, Hämatologie und Onkologie',
            'innere-medizin-und-kardiologie' => 'Innere Medizin und Kardiologie',
            'innere-medizin-und-nephrologie' => 'Innere Medizin und Nephrologie',
            'innere-medizin-und-pneumologie' => 'Innere Medizin und Pneumologie',
            'innere-medizin-und-rheumatologie' => 'Innere Medizin und Rheumatologie',
            'kinder-und-jugendmedizin' => 'Kinder- und Jugendmedizin',
            'kinder-und-jugendpsychiatrie-und-psychotherapie' => 'Kinder- und Jugendpsychiatrie und -psychotherapie',
            'laboratoriumsmedizin' => 'Laboratoriumsmedizin',
            'mikrobiologie-virologie-und-infektionsepidemiologie' => 'Mikrobiologie, Virologie und Infektionsepidemiologie',
            'mund-kiefer-gesichtschirurgie' => 'Mund-Kiefer-Gesichtschirurgie',
            'neurochirurgie' => 'Neurochirurgie',
            'neurologie' => 'Neurologie',
            'nuklearmedizin' => 'Nuklearmedizin',
            'pathologie' => 'Pathologie',
            'pharmakologie-und-toxikologie' => 'Pharmakologie und Toxikologie',
            'physikalische-und-rehabilitative-medizin' => 'Physikalische und Rehabilitative Medizin',
            'psychiatrie-und-psychotherapie' => 'Psychiatrie und Psychotherapie',
            'psychosomatische-medizin-und-psychotherapie' => 'Psychosomatische Medizin und Psychotherapie',
            'radiologie' => 'Radiologie',
            'rechtsmedizin' => 'Rechtsmedizin',
            'strahlentherapie' => 'Strahlentherapie',
            'transfusionsmedizin' => 'Transfusionsmedizin',
            'urologie' => 'Urologie',
            'ernahrungsmedizin-zusatzbezeichnung' => 'Ernährungsmedizin (Zusatzbezeichnung)',
            'naturheilverfahren-zusatzbezeichnung' => 'Naturheilverfahren (Zusatzbezeichnung)',
            'homoopathie-zusatzbezeichnung' => 'Homöopathie (Zusatzbezeichnung)',
        ],
        'ernahrungsberatung' => [
            'ernahrungsberater-in' => 'Ernährungsberater/in',
            'diatassistent-in' => 'Diätassistent/in',
            'okotrophologe-in' => 'Ökotrophologe/in',
            'ernahrungswissenschaftler-in' => 'Ernährungswissenschaftler/in',
            'ernahrungsmediziner-in' => 'Ernährungsmediziner/in',
            'ernahrungscoach' => 'Ernährungscoach',
            'ernahrungstherapeut-in' => 'Ernährungstherapeut/in',
            'klinische-r-ernahrungsberater-in' => 'Klinische/r Ernährungsberater/in',
            'fachkraft-fur-ernahrung-und-diatetik-ch' => 'Fachkraft für Ernährung und Diätetik (CH)',
            'diplomierte-r-ernahrungsberater-in-at-ch' => 'Diplomierte/r Ernährungsberater/in (AT/CH)',
        ],
        'heilberuf' => [
            'heilpraktiker-in-allgemein' => 'Heilpraktiker/in (allgemein)',
            'heilpraktiker-in-beschrankt-auf-psychotherapie' => 'Heilpraktiker/in – beschränkt auf Psychotherapie',
            'physiotherapeut-in' => 'Physiotherapeut/in',
            'ergotherapeut-in' => 'Ergotherapeut/in',
            'logopade-in' => 'Logopäde/in',
            'podologe-in' => 'Podologe/in',
            'hebamme-entbindungspfleger' => 'Hebamme / Entbindungspfleger',
            'masseur-in-und-medizinische-r-bademeister-in' => 'Masseur/in und medizinische/r Bademeister/in',
            'pflegefachperson-gesundheits-und-krankenpfleger-in' => 'Pflegefachperson / Gesundheits- und Krankenpfleger/in',
            'medizinische-r-fachangestellte-r-mfa' => 'Medizinische/r Fachangestellte/r (MFA)',
            'rettungssanitater-in-notfallsanitater-in' => 'Rettungssanitäter/in / Notfallsanitäter/in',
            'pharmazeut-in-apotheker-in' => 'Pharmazeut/in / Apotheker/in',
        ],
        'therapeut' => [
            'physiotherapeut-in' => 'Physiotherapeut/in',
            'ergotherapeut-in' => 'Ergotherapeut/in',
            'psychotherapeut-in-verhaltenstherapie' => 'Psychotherapeut/in (Verhaltenstherapie)',
            'psychotherapeut-in-tiefenpsychologie-psychoanalyse' => 'Psychotherapeut/in (Tiefenpsychologie/Psychoanalyse)',
            'systemische-r-therapeut-in' => 'Systemische/r Therapeut/in',
            'kunsttherapeut-in' => 'Kunsttherapeut/in',
            'musiktherapeut-in' => 'Musiktherapeut/in',
            'atemtherapeut-in' => 'Atemtherapeut/in',
            'traumatherapeut-in' => 'Traumatherapeut/in',
            'manualtherapeut-in' => 'Manualtherapeut/in',
            'craniosacral-therapeut-in' => 'Craniosacral-Therapeut/in',
            'yogatherapeut-in' => 'Yogatherapeut/in',
            'entspannungstherapeut-in' => 'Entspannungstherapeut/in',
            'sporttherapeut-in' => 'Sporttherapeut/in',
        ],
        'psychologie-und-psychiatrie' => [
            'psychologe-in-dipl-m-sc' => 'Psychologe/in (Dipl./M.Sc.)',
            'klinische-r-psychologe-in' => 'Klinische/r Psychologe/in',
            'psychologische-r-psychotherapeut-in' => 'Psychologische/r Psychotherapeut/in',
            'kinder-und-jugendlichenpsychotherapeut-in' => 'Kinder- und Jugendlichenpsychotherapeut/in',
            'facharzt-arztin-fur-psychiatrie-und-psychotherapie' => 'Facharzt/-ärztin für Psychiatrie und Psychotherapie',
            'facharzt-arztin-fur-psychosomatische-medizin' => 'Facharzt/-ärztin für Psychosomatische Medizin',
            'neuropsychologe-in' => 'Neuropsychologe/in',
            'gesundheitspsychologe-in-at' => 'Gesundheitspsychologe/in (AT)',
        ],
        'osteopathie' => [
            'osteopath-in-d-o' => 'Osteopath/in (D.O.)',
            'heilpraktiker-in-fur-osteopathie' => 'Heilpraktiker/in für Osteopathie',
            'physiotherapeut-in-mit-osteopathischer-zusatzqualifikation' => 'Physiotherapeut/in mit osteopathischer Zusatzqualifikation',
            'cranio-sacral-osteopath-in' => 'Cranio-Sacral-Osteopath/in',
            'viszeralosteopath-in' => 'Viszeralosteopath/in',
        ],
        'klinik-und-institution' => [
            'universitatsklinik' => 'Universitätsklinik',
            'fachklinik-fur-gastroenterologie' => 'Fachklinik für Gastroenterologie',
            'rehaklinik' => 'Rehaklinik',
            'psychosomatische-klinik' => 'Psychosomatische Klinik',
            'privatklinik' => 'Privatklinik',
            'medizinisches-versorgungszentrum-mvz' => 'Medizinisches Versorgungszentrum (MVZ)',
            'tagesklinik' => 'Tagesklinik',
            'kurklinik-sanatorium' => 'Kurklinik / Sanatorium',
            'gesundheitszentrum-interdisziplinare-praxis' => 'Gesundheitszentrum / interdisziplinäre Praxis',
        ],
        'wissenschaft' => [
            'mikrobiom-forscher-in' => 'Mikrobiom-Forscher/in',
            'ernahrungswissenschaftler-in' => 'Ernährungswissenschaftler/in',
            'molekularbiologe-in' => 'Molekularbiologe/in',
            'biologe-in' => 'Biologe/in',
            'biochemiker-in' => 'Biochemiker/in',
            'epidemiologe-in' => 'Epidemiologe/in',
            'public-health-wissenschaftler-in' => 'Public-Health-Wissenschaftler/in',
            'universitats-institutsforscher-in' => 'Universitäts-/Institutsforscher/in',
            'doktorand-in' => 'Doktorand/in',
            'postdoktorand-in' => 'Postdoktorand/in',
        ],
    ];

    private const SALUTATIONS = [
        'frau' => 'Frau',
        'herr' => 'Herr',
        'divers' => 'Divers',
        'keine-angabe' => 'Keine Angabe',
    ];

    private const TITLES = [
        'kein-titel' => '(kein Titel)',
        'dr-med' => 'Dr. med.',
        'dr' => 'Dr.',
        'prof-dr' => 'Prof. Dr.',
        'prof-dr-med' => 'Prof. Dr. med.',
        'pd-dr' => 'PD Dr.',
        'mag' => 'Mag.',
        'dipl-ing' => 'Dipl.-Ing.',
        'dipl-oecotrophologe-in' => 'Dipl.-Oecotrophologe/in',
    ];

    private const COUNTRIES = [
        'deutschland' => 'Deutschland',
        'osterreich' => 'Österreich',
        'schweiz' => 'Schweiz',
        'liechtenstein' => 'Liechtenstein',
        'sonstiges' => 'Sonstiges',
    ];

    private const MAIN_CATEGORIES_EN = [
        'facharztrichtung' => 'Medical Specialty (Facharzt)',
        'ernahrungsberatung' => 'Nutrition Counseling',
        'heilberuf' => 'Healthcare Profession',
        'therapeut' => 'Therapist',
        'psychologie-und-psychiatrie' => 'Psychology and Psychiatry',
        'osteopathie' => 'Osteopathy',
        'klinik-und-institution' => 'Clinic and Institution',
        'wissenschaft' => 'Science',
    ];

    private const SPECIALTIES_BY_CATEGORY_EN = [
        'facharztrichtung' => [
            'allgemeinmedizin' => 'General Medicine',
            'anasthesiologie' => 'Anesthesiology',
            'arbeitsmedizin' => 'Occupational Medicine',
            'augenheilkunde' => 'Ophthalmology',
            'chirurgie-allgemein-und-viszeralchirurgie' => 'Surgery (General and Visceral Surgery)',
            'gefaschirurgie' => 'Vascular Surgery',
            'herzchirurgie' => 'Cardiac Surgery',
            'kinderchirurgie' => 'Pediatric Surgery',
            'orthopadie-und-unfallchirurgie' => 'Orthopedics and Trauma Surgery',
            'plastische-rekonstruktive-und-asthetische-chirurgie' => 'Plastic, Reconstructive and Aesthetic Surgery',
            'thoraxchirurgie' => 'Thoracic Surgery',
            'frauenheilkunde-und-geburtshilfe' => 'Gynecology and Obstetrics',
            'hals-nasen-ohrenheilkunde' => 'Otorhinolaryngology (ENT)',
            'haut-und-geschlechtskrankheiten-dermatologie' => 'Dermatology and Venereology',
            'humangenetik' => 'Human Genetics',
            'hygiene-und-umweltmedizin' => 'Hygiene and Environmental Medicine',
            'innere-medizin-und-angiologie' => 'Internal Medicine and Angiology',
            'innere-medizin-endokrinologie-und-diabetologie' => 'Internal Medicine, Endocrinology and Diabetology',
            'innere-medizin-und-gastroenterologie' => 'Internal Medicine and Gastroenterology',
            'innere-medizin-hamatologie-und-onkologie' => 'Internal Medicine, Hematology and Oncology',
            'innere-medizin-und-kardiologie' => 'Internal Medicine and Cardiology',
            'innere-medizin-und-nephrologie' => 'Internal Medicine and Nephrology',
            'innere-medizin-und-pneumologie' => 'Internal Medicine and Pulmonology',
            'innere-medizin-und-rheumatologie' => 'Internal Medicine and Rheumatology',
            'kinder-und-jugendmedizin' => 'Pediatric and Adolescent Medicine',
            'kinder-und-jugendpsychiatrie-und-psychotherapie' => 'Child and Adolescent Psychiatry and Psychotherapy',
            'laboratoriumsmedizin' => 'Laboratory Medicine',
            'mikrobiologie-virologie-und-infektionsepidemiologie' => 'Microbiology, Virology and Infection Epidemiology',
            'mund-kiefer-gesichtschirurgie' => 'Oral and Maxillofacial Surgery',
            'neurochirurgie' => 'Neurosurgery',
            'neurologie' => 'Neurology',
            'nuklearmedizin' => 'Nuclear Medicine',
            'pathologie' => 'Pathology',
            'pharmakologie-und-toxikologie' => 'Pharmacology and Toxicology',
            'physikalische-und-rehabilitative-medizin' => 'Physical and Rehabilitative Medicine',
            'psychiatrie-und-psychotherapie' => 'Psychiatry and Psychotherapy',
            'psychosomatische-medizin-und-psychotherapie' => 'Psychosomatic Medicine and Psychotherapy',
            'radiologie' => 'Radiology',
            'rechtsmedizin' => 'Forensic Medicine',
            'strahlentherapie' => 'Radiation Oncology',
            'transfusionsmedizin' => 'Transfusion Medicine',
            'urologie' => 'Urology',
            'ernahrungsmedizin-zusatzbezeichnung' => 'Nutritional Medicine (additional qualification)',
            'naturheilverfahren-zusatzbezeichnung' => 'Naturopathic Treatment (additional qualification)',
            'homoopathie-zusatzbezeichnung' => 'Homeopathy (additional qualification)',
        ],
        'ernahrungsberatung' => [
            'ernahrungsberater-in' => 'Nutrition Counselor',
            'diatassistent-in' => 'Dietetic Assistant',
            'okotrophologe-in' => 'Home Economics / Nutrition Scientist (Ökotrophologe)',
            'ernahrungswissenschaftler-in' => 'Nutrition Scientist',
            'ernahrungsmediziner-in' => 'Nutritional Physician',
            'ernahrungscoach' => 'Nutrition Coach',
            'ernahrungstherapeut-in' => 'Nutrition Therapist',
            'klinische-r-ernahrungsberater-in' => 'Clinical Nutrition Counselor',
            'fachkraft-fur-ernahrung-und-diatetik-ch' => 'Certified Nutrition and Dietetics Specialist (CH)',
            'diplomierte-r-ernahrungsberater-in-at-ch' => 'Certified Nutrition Counselor (AT/CH)',
        ],
        'heilberuf' => [
            'heilpraktiker-in-allgemein' => 'Alternative Practitioner (Heilpraktiker, general)',
            'heilpraktiker-in-beschrankt-auf-psychotherapie' => 'Alternative Practitioner limited to Psychotherapy',
            'physiotherapeut-in' => 'Physiotherapist',
            'ergotherapeut-in' => 'Occupational Therapist',
            'logopade-in' => 'Speech-Language Therapist',
            'podologe-in' => 'Podiatrist',
            'hebamme-entbindungspfleger' => 'Midwife',
            'masseur-in-und-medizinische-r-bademeister-in' => 'Masseur and Medical Bath Attendant',
            'pflegefachperson-gesundheits-und-krankenpfleger-in' => 'Registered Nurse',
            'medizinische-r-fachangestellte-r-mfa' => 'Medical Assistant (MFA)',
            'rettungssanitater-in-notfallsanitater-in' => 'Paramedic / Emergency Medical Technician',
            'pharmazeut-in-apotheker-in' => 'Pharmacist',
        ],
        'therapeut' => [
            'physiotherapeut-in' => 'Physiotherapist',
            'ergotherapeut-in' => 'Occupational Therapist',
            'psychotherapeut-in-verhaltenstherapie' => 'Psychotherapist (Cognitive Behavioral Therapy)',
            'psychotherapeut-in-tiefenpsychologie-psychoanalyse' => 'Psychotherapist (Depth Psychology / Psychoanalysis)',
            'systemische-r-therapeut-in' => 'Systemic Therapist',
            'kunsttherapeut-in' => 'Art Therapist',
            'musiktherapeut-in' => 'Music Therapist',
            'atemtherapeut-in' => 'Breathing Therapist',
            'traumatherapeut-in' => 'Trauma Therapist',
            'manualtherapeut-in' => 'Manual Therapist',
            'craniosacral-therapeut-in' => 'Craniosacral Therapist',
            'yogatherapeut-in' => 'Yoga Therapist',
            'entspannungstherapeut-in' => 'Relaxation Therapist',
            'sporttherapeut-in' => 'Sports Therapist',
        ],
        'psychologie-und-psychiatrie' => [
            'psychologe-in-dipl-m-sc' => 'Psychologist (Dipl./M.Sc.)',
            'klinische-r-psychologe-in' => 'Clinical Psychologist',
            'psychologische-r-psychotherapeut-in' => 'Psychological Psychotherapist',
            'kinder-und-jugendlichenpsychotherapeut-in' => 'Child and Adolescent Psychotherapist',
            'facharzt-arztin-fur-psychiatrie-und-psychotherapie' => 'Specialist in Psychiatry and Psychotherapy',
            'facharzt-arztin-fur-psychosomatische-medizin' => 'Specialist in Psychosomatic Medicine',
            'neuropsychologe-in' => 'Neuropsychologist',
            'gesundheitspsychologe-in-at' => 'Health Psychologist (AT)',
        ],
        'osteopathie' => [
            'osteopath-in-d-o' => 'Osteopath (D.O.)',
            'heilpraktiker-in-fur-osteopathie' => 'Alternative Practitioner for Osteopathy',
            'physiotherapeut-in-mit-osteopathischer-zusatzqualifikation' => 'Physiotherapist with Osteopathic Additional Qualification',
            'cranio-sacral-osteopath-in' => 'Cranio-Sacral Osteopath',
            'viszeralosteopath-in' => 'Visceral Osteopath',
        ],
        'klinik-und-institution' => [
            'universitatsklinik' => 'University Hospital',
            'fachklinik-fur-gastroenterologie' => 'Specialist Clinic for Gastroenterology',
            'rehaklinik' => 'Rehabilitation Clinic',
            'psychosomatische-klinik' => 'Psychosomatic Clinic',
            'privatklinik' => 'Private Clinic',
            'medizinisches-versorgungszentrum-mvz' => 'Medical Care Center (MVZ)',
            'tagesklinik' => 'Day Clinic',
            'kurklinik-sanatorium' => 'Spa Clinic / Sanatorium',
            'gesundheitszentrum-interdisziplinare-praxis' => 'Health Center / Interdisciplinary Practice',
        ],
        'wissenschaft' => [
            'mikrobiom-forscher-in' => 'Microbiome Researcher',
            'ernahrungswissenschaftler-in' => 'Nutrition Scientist',
            'molekularbiologe-in' => 'Molecular Biologist',
            'biologe-in' => 'Biologist',
            'biochemiker-in' => 'Biochemist',
            'epidemiologe-in' => 'Epidemiologist',
            'public-health-wissenschaftler-in' => 'Public Health Scientist',
            'universitats-institutsforscher-in' => 'University / Institute Researcher',
            'doktorand-in' => 'Doctoral Candidate',
            'postdoktorand-in' => 'Postdoctoral Researcher',
        ],
    ];

    private const SALUTATIONS_EN = [
        'frau' => 'Ms.',
        'herr' => 'Mr.',
        'divers' => 'Diverse',
        'keine-angabe' => 'Prefer not to say',
    ];

    private const TITLES_EN = [
        'kein-titel' => '(No title)',
        'dr-med' => 'Dr. med.',
        'dr' => 'Dr.',
        'prof-dr' => 'Prof. Dr.',
        'prof-dr-med' => 'Prof. Dr. med.',
        'pd-dr' => 'PD Dr.',
        'mag' => 'Mag.',
        'dipl-ing' => 'Dipl.-Ing.',
        'dipl-oecotrophologe-in' => 'Dipl.-Oecotrophologe/in (Home Economics Graduate)',
    ];

    private const COUNTRIES_EN = [
        'deutschland' => 'Germany',
        'osterreich' => 'Austria',
        'schweiz' => 'Switzerland',
        'liechtenstein' => 'Liechtenstein',
        'sonstiges' => 'Other',
    ];

    public function __construct() {
        add_action('init', [$this, 'capture_language_choice']);
        add_action('init', [$this, 'register_application_post_type']);
        add_shortcode(self::SHORTCODE, [$this, 'render_registration_form']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'handle_application_submission']);
        add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, [$this, 'handle_application_submission']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'ensure_manage_capability_granted']);
        add_action('admin_post_db_application_decision', [$this, 'handle_application_decision']);
        add_action('admin_post_db_save_platform_settings', [$this, 'handle_save_platform_settings']);
        add_action('admin_post_db_platform_retry', [$this, 'handle_platform_retry']);
        add_action('admin_post_db_save_privacy_policy', [$this, 'handle_save_privacy_policy']);
        add_action('admin_post_' . self::DOWNLOAD_ACTION, [$this, 'handle_document_download']);
        add_action('add_meta_boxes', [$this, 'register_application_meta_boxes']);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'filter_application_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'render_application_column'], 10, 2);
    }

    public static function activate(): void {
        $role = get_role('administrator');
        if ($role && !$role->has_cap(self::MANAGE_CAPABILITY)) {
            $role->add_cap(self::MANAGE_CAPABILITY);
        }
    }

    public function ensure_manage_capability_granted(): void {
        $role = get_role('administrator');
        if ($role && !$role->has_cap(self::MANAGE_CAPABILITY)) {
            $role->add_cap(self::MANAGE_CAPABILITY);
        }
    }

    public function capture_language_choice(): void {
        $requested_language = isset($_GET['db_lang']) ? sanitize_key((string) $_GET['db_lang']) : '';

        if (!$this->is_supported_language($requested_language)) {
            return;
        }

        $this->current_language = $requested_language;
        setcookie(
            self::LANGUAGE_COOKIE,
            $requested_language,
            [
                'expires' => time() + YEAR_IN_SECONDS,
                'path' => COOKIEPATH ?: '/',
                'secure' => is_ssl(),
                'httponly' => false,
                'samesite' => 'Lax',
            ]
        );
        $_COOKIE[self::LANGUAGE_COOKIE] = $requested_language;
    }

    private function is_supported_language(string $language): bool {
        return in_array($language, ['de', 'en'], true);
    }

    /**
     * Picklist labels for the currently active form language. Slugs (array
     * keys) are identical across languages and are what's actually stored
     * and validated — only the display label differs.
     */
    private function get_main_categories(): array {
        return $this->get_current_language() === 'en' ? self::MAIN_CATEGORIES_EN : self::MAIN_CATEGORIES;
    }

    private function get_specialties_by_category(): array {
        return $this->get_current_language() === 'en' ? self::SPECIALTIES_BY_CATEGORY_EN : self::SPECIALTIES_BY_CATEGORY;
    }

    private function get_salutations(): array {
        return $this->get_current_language() === 'en' ? self::SALUTATIONS_EN : self::SALUTATIONS;
    }

    private function get_titles(): array {
        return $this->get_current_language() === 'en' ? self::TITLES_EN : self::TITLES;
    }

    private function get_countries(): array {
        return $this->get_current_language() === 'en' ? self::COUNTRIES_EN : self::COUNTRIES;
    }

    public function get_current_language(): string {
        if ($this->current_language !== null) {
            return $this->current_language;
        }

        $cookie_language = isset($_COOKIE[self::LANGUAGE_COOKIE]) ? sanitize_key((string) $_COOKIE[self::LANGUAGE_COOKIE]) : '';
        if ($this->is_supported_language($cookie_language)) {
            $this->current_language = $cookie_language;
            return $this->current_language;
        }

        $locale = determine_locale();
        $this->current_language = str_starts_with($locale, 'de') ? 'de' : self::DEFAULT_LANGUAGE;

        return $this->current_language;
    }

    private function get_translations(string $language): array {
        if (isset($this->translations[$language])) {
            return $this->translations[$language];
        }

        $path = plugin_dir_path(__FILE__) . 'languages/' . $language . '.json';
        if (!is_readable($path)) {
            $path = plugin_dir_path(__FILE__) . 'languages/' . self::DEFAULT_LANGUAGE . '.json';
        }

        $contents = is_readable($path) ? file_get_contents($path) : '{}';
        $decoded = json_decode((string) $contents, true);
        $this->translations[$language] = is_array($decoded) ? $decoded : [];

        return $this->translations[$language];
    }

    public function t(string $key): string {
        $translations = $this->get_translations($this->get_current_language());

        return $translations[$key] ?? $this->get_translations(self::DEFAULT_LANGUAGE)[$key] ?? $key;
    }

    private function t_for_language(string $key, string $language): string {
        $language = $this->is_supported_language($language) ? $language : self::DEFAULT_LANGUAGE;
        $translations = $this->get_translations($language);

        return $translations[$key] ?? $this->get_translations(self::DEFAULT_LANGUAGE)[$key] ?? $key;
    }

    private function render_language_switcher(): string {
        $current_language = $this->get_current_language();
        $languages = [
            'de' => ['flag' => '🇩🇪', 'label' => 'Deutsch'],
            'en' => ['flag' => '🇬🇧', 'label' => 'English'],
        ];

        $items = '';
        foreach ($languages as $language => $meta) {
            $items .= sprintf(
                '<a class="db-language-switcher__link %s" href="%s" aria-label="%s" title="%s"><span aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
                $current_language === $language ? 'is-active' : '',
                esc_url(add_query_arg('db_lang', $language)),
                esc_attr($meta['label']),
                esc_attr($meta['label']),
                esc_html($meta['flag']),
                esc_html($meta['label'])
            );
        }

        return '<div class="db-language-switcher" aria-label="Language switcher">' . $items . '</div>';
    }

    public function register_application_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Darmbulanz Applications',
                'singular_name' => 'Darmbulanz Application',
                'add_new_item' => 'Add Application',
                'edit_item' => 'Application Details',
                'view_item' => 'View Application',
                'search_items' => 'Search Applications',
                'not_found' => 'No applications found',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_admin_bar' => false,
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => [
                'edit_posts' => self::MANAGE_CAPABILITY,
                'edit_others_posts' => self::MANAGE_CAPABILITY,
                'publish_posts' => self::MANAGE_CAPABILITY,
                'read_private_posts' => self::MANAGE_CAPABILITY,
                'delete_posts' => self::MANAGE_CAPABILITY,
                'delete_others_posts' => self::MANAGE_CAPABILITY,
            ],
            'map_meta_cap' => true,
        ]);
    }

    public function register_assets(): void {
        $base_url = plugin_dir_url(__FILE__);

        wp_enqueue_style(
            'darmbulanz-inter-font',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            [],
            null
        );

        wp_enqueue_style(
            'darmbulanz-survey',
            $base_url . 'assets/css/registration.css',
            ['darmbulanz-inter-font'],
            self::VERSION
        );

        wp_register_script(
            'darmbulanz-survey',
            $base_url . 'assets/js/registration.js',
            [],
            self::VERSION,
            true
        );
    }

    public function render_registration_form(): string {
        wp_enqueue_script('darmbulanz-survey');
        $config = $this->get_client_config();
        wp_localize_script('darmbulanz-survey', 'DarmbulanzSurvey', $config);

        $darmbulanz_survey_config = $config;

        ob_start();
        require plugin_dir_path(__FILE__) . 'templates/registration-form.php';
        return (string) ob_get_clean();
    }

    private function get_client_config(): array {
        $language = $this->get_current_language();
        $translations = $this->get_translations($language);
        $keys = [
            'field.selectPlaceholder',
            'field.specialtyPlaceholder',
            'js.submitting',
            'js.submitError',
            'js.selectSpecialtyFirst',
            'button.submit',
            'validation.required',
            'validation.email',
            'validation.phone',
            'validation.consentRequired',
            'validation.categoryRequired',
            'validation.specialtyRequired',
            'validation.fileRequired',
            'validation.fileType',
            'validation.fileSize',
            'validation.tooManyFiles',
        ];

        $picked = [];
        foreach ($keys as $key) {
            $picked[$key] = $translations[$key] ?? $key;
        }

        return [
            'language' => $language,
            'text' => $picked,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'action' => self::AJAX_ACTION,
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'maxFiles' => self::MAX_UPLOAD_FILES,
            'specialtiesByCategory' => $this->get_specialties_by_category(),
        ];
    }

    private function get_privacy_policy_url(): string {
        $url = (string) get_option(self::PRIVACY_POLICY_OPTION, '');

        return $url !== '' ? $url : '#';
    }

    private function get_consent_label(): string {
        $label = $this->t('field.consent');

        return strtr($label, [
            '{privacy_policy_link}' => '<a href="' . esc_url($this->get_privacy_policy_url()) . '" target="_blank" rel="noopener noreferrer">' . esc_html($this->t('field.consentLinkText')) . '</a>',
        ]);
    }

    private function posted_text(string $key): string {
        return sanitize_text_field((string) wp_unslash($_POST[$key] ?? ''));
    }

    private function posted_textarea(string $key): string {
        return sanitize_textarea_field((string) wp_unslash($_POST[$key] ?? ''));
    }

    private function is_valid_phone(string $phone): bool {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return (bool) preg_match('/^\+?[\d\s().-]{7,24}$/', $phone) && strlen($digits) >= 7 && strlen($digits) <= 20;
    }

    private function get_rest_client_ip(): string {
        $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        return $remote_addr !== '' ? $remote_addr : 'unknown';
    }

    private function is_submission_rate_limited(): bool {
        $key = 'db_submission_rate_' . md5($this->get_rest_client_ip());
        $count = (int) get_transient($key);

        if ($count >= 8) {
            return true;
        }

        set_transient($key, $count + 1, HOUR_IN_SECONDS);

        return false;
    }

    private function get_latest_application_status_by_email(string $email): ?string {
        $query = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
            'meta_query' => [
                ['key' => self::META_PREFIX . 'email', 'value' => $email, 'compare' => '='],
            ],
        ]);

        if (empty($query->posts)) {
            return null;
        }

        return $this->get_application_status((int) $query->posts[0]);
    }

    private function get_application_status(int $post_id): string {
        $status = (string) get_post_meta($post_id, self::META_STATUS, true);

        return in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : 'pending';
    }

    public function handle_application_submission(): void {
        $language = isset($_POST['language']) ? sanitize_key((string) wp_unslash($_POST['language'])) : '';
        if ($this->is_supported_language($language)) {
            $this->current_language = $language;
        }

        if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
            wp_send_json_error(['message' => $this->t('ajax.invalidRequest')], 403);
        }

        if ($this->is_submission_rate_limited()) {
            wp_send_json_error(['message' => $this->t('ajax.rateLimited')], 429);
        }

        if ($this->posted_text('website_hp') !== '') {
            // Honeypot: real applicants never see or fill this field. Pretend success
            // without storing anything, so automated fillers don't learn to avoid it.
            wp_send_json_success(['message' => $this->t('ajax.success')]);
        }

        $salutation = $this->posted_text('salutation');
        $title = $this->posted_text('title');
        $first_name = $this->posted_text('first_name');
        $last_name = $this->posted_text('last_name');
        $main_category = $this->posted_text('main_category');
        $specialty = $this->posted_text('specialty');
        $additional_qualifications = $this->posted_text('additional_qualifications');
        $license_number = $this->posted_text('license_number');
        $institution = $this->posted_text('institution');
        $street = $this->posted_text('street');
        $postal_code = $this->posted_text('postal_code');
        $city = $this->posted_text('city');
        $country = $this->posted_text('country');
        $phone = $this->posted_text('phone');
        $email = sanitize_email((string) wp_unslash($_POST['email'] ?? ''));
        $website = esc_url_raw((string) wp_unslash($_POST['website'] ?? ''));
        $social_media = $this->posted_text('social_media');
        $focus_areas = $this->posted_textarea('focus_areas');
        $interest_referral = !empty($_POST['interest_referral']);
        $interest_content = !empty($_POST['interest_content']);
        $interest_training = !empty($_POST['interest_training']);
        $interest_research = !empty($_POST['interest_research']);
        $remarks = $this->posted_textarea('remarks');
        $consent = !empty($_POST['consent']);

        if ($salutation === '' || $first_name === '' || $last_name === '' || $main_category === '' || $specialty === '' || $institution === '' || $country === '' || $phone === '' || !is_email($email) || !$consent) {
            wp_send_json_error(['message' => $this->t('ajax.requiredFields')], 400);
        }

        if (!array_key_exists($salutation, self::SALUTATIONS)) {
            wp_send_json_error(['message' => $this->t('ajax.requiredFields')], 400);
        }

        if ($title !== '' && !array_key_exists($title, self::TITLES)) {
            wp_send_json_error(['message' => $this->t('ajax.requiredFields')], 400);
        }

        if (!array_key_exists($main_category, self::MAIN_CATEGORIES)) {
            wp_send_json_error(['message' => $this->t('ajax.categoryInvalid')], 400);
        }

        if (!array_key_exists($specialty, self::SPECIALTIES_BY_CATEGORY[$main_category] ?? [])) {
            wp_send_json_error(['message' => $this->t('ajax.specialtyInvalid')], 400);
        }

        if (!array_key_exists($country, self::COUNTRIES)) {
            wp_send_json_error(['message' => $this->t('ajax.countryInvalid')], 400);
        }

        if (!$this->is_valid_phone($phone)) {
            wp_send_json_error(['message' => $this->t('validation.phone')], 400);
        }

        $existing_status = $this->get_latest_application_status_by_email($email);
        if ($existing_status === 'approved') {
            wp_send_json_error(['message' => $this->t('ajax.emailApproved')], 409);
        }
        if ($existing_status === 'pending') {
            wp_send_json_error(['message' => $this->t('ajax.emailPending')], 409);
        }

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => sprintf('%s %s - %s', $first_name, $last_name, $email),
            'post_content' => '',
        ], true);

        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => $this->t('ajax.storageError')], 500);
        }

        $attachment_ids = $this->handle_application_uploads((int) $post_id);
        if (is_wp_error($attachment_ids)) {
            wp_delete_post((int) $post_id, true);
            $error_code = $attachment_ids->get_error_code();
            $message_key = match ($error_code) {
                'file_too_large' => 'validation.fileSize',
                'invalid_file_type' => 'validation.fileType',
                'too_many_files' => 'validation.tooManyFiles',
                default => 'validation.fileRequired',
            };
            wp_send_json_error(['message' => $this->t($message_key)], 400);
        }

        update_post_meta((int) $post_id, self::META_STATUS, 'pending');
        update_post_meta((int) $post_id, self::META_PREFIX . 'salutation', $salutation);
        update_post_meta((int) $post_id, self::META_PREFIX . 'title', $title);
        update_post_meta((int) $post_id, self::META_PREFIX . 'first_name', $first_name);
        update_post_meta((int) $post_id, self::META_PREFIX . 'last_name', $last_name);
        update_post_meta((int) $post_id, self::META_PREFIX . 'main_category', $main_category);
        update_post_meta((int) $post_id, self::META_PREFIX . 'specialty', $specialty);
        update_post_meta((int) $post_id, self::META_PREFIX . 'additional_qualifications', $additional_qualifications);
        update_post_meta((int) $post_id, self::META_PREFIX . 'license_number', $license_number);
        update_post_meta((int) $post_id, self::META_PREFIX . 'institution', $institution);
        update_post_meta((int) $post_id, self::META_PREFIX . 'street', $street);
        update_post_meta((int) $post_id, self::META_PREFIX . 'postal_code', $postal_code);
        update_post_meta((int) $post_id, self::META_PREFIX . 'city', $city);
        update_post_meta((int) $post_id, self::META_PREFIX . 'country', $country);
        update_post_meta((int) $post_id, self::META_PREFIX . 'phone', $phone);
        update_post_meta((int) $post_id, self::META_PREFIX . 'email', $email);
        update_post_meta((int) $post_id, self::META_PREFIX . 'website', $website);
        update_post_meta((int) $post_id, self::META_PREFIX . 'social_media', $social_media);
        update_post_meta((int) $post_id, self::META_PREFIX . 'focus_areas', $focus_areas);
        update_post_meta((int) $post_id, self::META_PREFIX . 'interest_referral', $interest_referral ? '1' : '0');
        update_post_meta((int) $post_id, self::META_PREFIX . 'interest_content', $interest_content ? '1' : '0');
        update_post_meta((int) $post_id, self::META_PREFIX . 'interest_training', $interest_training ? '1' : '0');
        update_post_meta((int) $post_id, self::META_PREFIX . 'interest_research', $interest_research ? '1' : '0');
        update_post_meta((int) $post_id, self::META_PREFIX . 'remarks', $remarks);
        update_post_meta((int) $post_id, self::META_PREFIX . 'language', $language ?: $this->get_current_language());
        update_post_meta((int) $post_id, self::META_PREFIX . 'consent_agreed', '1');
        update_post_meta((int) $post_id, self::META_PREFIX . 'submitted_at', current_time('mysql'));
        update_post_meta((int) $post_id, self::META_PREFIX . 'document_attachment_ids', $attachment_ids);
        if ($existing_status === 'rejected') {
            update_post_meta((int) $post_id, self::META_PREFIX . 'resubmitted_after_rejection', '1');
        }

        wp_send_json_success(['message' => $this->t('ajax.success')]);
    }

    /**
     * @return int[]|WP_Error
     */
    private function handle_application_uploads(int $post_id): array|WP_Error {
        if (empty($_FILES['documents']) || empty($_FILES['documents']['name']) || !is_array($_FILES['documents']['name'])) {
            return new WP_Error('missing_file', 'Required file is missing.');
        }

        $count = count($_FILES['documents']['name']);
        if ($count > self::MAX_UPLOAD_FILES) {
            return new WP_Error('too_many_files', 'Too many files.');
        }

        $allowed_mimes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_ids = [];
        add_filter('upload_dir', [$this, 'redirect_upload_to_protected_folder']);

        for ($i = 0; $i < $count; $i++) {
            if ((int) ($_FILES['documents']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ((int) ($_FILES['documents']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                remove_filter('upload_dir', [$this, 'redirect_upload_to_protected_folder']);
                $this->delete_attachments($attachment_ids);
                return new WP_Error('upload_error', 'Upload failed.');
            }

            if ((int) ($_FILES['documents']['size'][$i] ?? 0) > 10 * MB_IN_BYTES) {
                remove_filter('upload_dir', [$this, 'redirect_upload_to_protected_folder']);
                $this->delete_attachments($attachment_ids);
                return new WP_Error('file_too_large', 'File is too large.');
            }

            $filetype = wp_check_filetype((string) ($_FILES['documents']['name'][$i] ?? ''), $allowed_mimes);
            if (empty($filetype['ext']) || empty($filetype['type'])) {
                remove_filter('upload_dir', [$this, 'redirect_upload_to_protected_folder']);
                $this->delete_attachments($attachment_ids);
                return new WP_Error('invalid_file_type', 'File type is not allowed.');
            }

            $_FILES['db_single_upload'] = [
                'name' => $_FILES['documents']['name'][$i],
                'type' => $_FILES['documents']['type'][$i],
                'tmp_name' => $_FILES['documents']['tmp_name'][$i],
                'error' => $_FILES['documents']['error'][$i],
                'size' => $_FILES['documents']['size'][$i],
            ];

            $attachment_id = media_handle_upload('db_single_upload', $post_id);
            unset($_FILES['db_single_upload']);

            if (is_wp_error($attachment_id)) {
                remove_filter('upload_dir', [$this, 'redirect_upload_to_protected_folder']);
                $this->delete_attachments($attachment_ids);
                return $attachment_id;
            }

            $attachment_ids[] = (int) $attachment_id;
        }

        remove_filter('upload_dir', [$this, 'redirect_upload_to_protected_folder']);

        if ($attachment_ids === []) {
            return new WP_Error('missing_file', 'Required file is missing.');
        }

        return $attachment_ids;
    }

    private function delete_attachments(array $attachment_ids): void {
        foreach ($attachment_ids as $attachment_id) {
            wp_delete_attachment((int) $attachment_id, true);
        }
    }

    public function redirect_upload_to_protected_folder(array $dirs): array {
        $dirs['subdir'] = '/' . self::DOCUMENT_UPLOAD_SUBDIR . $dirs['subdir'];
        $dirs['path'] = $dirs['basedir'] . $dirs['subdir'];
        $dirs['url'] = $dirs['baseurl'] . $dirs['subdir'];

        $this->ensure_protected_folder_has_htaccess($dirs['basedir'] . '/' . self::DOCUMENT_UPLOAD_SUBDIR);

        return $dirs;
    }

    private function ensure_protected_folder_has_htaccess(string $folder_path): void {
        $htaccess_path = $folder_path . '/.htaccess';
        if (file_exists($htaccess_path)) {
            return;
        }

        if (!is_dir($folder_path)) {
            wp_mkdir_p($folder_path);
        }

        $rules = "# Darmbulanz: block direct access, files are served only through the admin download handler.\n"
            . "<IfModule mod_authz_core.c>\n"
            . "    Require all denied\n"
            . "</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n"
            . "    Order allow,deny\n"
            . "    Deny from all\n"
            . "</IfModule>\n";

        file_put_contents($htaccess_path, $rules);
    }

    public function register_admin_menu(): void {
        add_menu_page(
            'Darmbulanz Survey',
            'Darmbulanz Survey',
            self::MANAGE_CAPABILITY,
            'darmbulanz-survey',
            [$this, 'render_admin_applications_page'],
            'dashicons-groups',
            26
        );

        add_submenu_page(
            'darmbulanz-survey',
            'Applications',
            'Applications',
            self::MANAGE_CAPABILITY,
            'darmbulanz-survey',
            [$this, 'render_admin_applications_page']
        );

        add_submenu_page(
            'darmbulanz-survey',
            'Application Records',
            'Application Records',
            self::MANAGE_CAPABILITY,
            'edit.php?post_type=' . self::POST_TYPE
        );

        add_submenu_page(
            'darmbulanz-survey',
            'Platform Settings',
            'Platform Settings',
            self::MANAGE_CAPABILITY,
            'darmbulanz-survey-settings',
            [$this, 'render_platform_settings_page']
        );

        add_submenu_page(
            'darmbulanz-survey',
            'Privacy Policy',
            'Privacy Policy',
            self::MANAGE_CAPABILITY,
            'db_privacy_policy',
            [$this, 'render_privacy_policy_page']
        );

        add_submenu_page(
            'darmbulanz-survey',
            'Help',
            'Help',
            self::MANAGE_CAPABILITY,
            'db_help',
            [$this, 'render_help_page']
        );
    }

    public function render_admin_applications_page(): void {
        if (!current_user_can(self::MANAGE_CAPABILITY)) {
            wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'darmbulanz-survey'));
        }

        $status = isset($_GET['status']) ? sanitize_key((string) wp_unslash($_GET['status'])) : 'pending';
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $status = 'pending';
        }

        $per_page = $this->get_applications_per_page();
        $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;

        $applications = new WP_Query([
            'post_type' => self::POST_TYPE,
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                ['key' => self::META_STATUS, 'value' => $status],
            ],
        ]);

        echo '<div class="wrap db-admin">';
        echo '<h1>Darmbulanz Applications</h1>';
        $this->render_admin_notice();
        $this->render_status_tabs($status);
        $this->render_per_page_selector($status, $per_page);

        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>Applicant</th><th>Email</th><th>Category / Specialty</th><th>Documents</th><th>Submitted</th><th>Status</th><th>Platform</th><th>Actions</th>';
        echo '</tr></thead><tbody>';

        if ($applications->have_posts()) {
            while ($applications->have_posts()) {
                $applications->the_post();
                $this->render_admin_application_row((int) get_the_ID());
            }
            wp_reset_postdata();
        } else {
            echo '<tr><td colspan="8">No applications found for this status.</td></tr>';
        }

        echo '</tbody></table>';
        $this->render_pagination($status, $per_page, $paged, (int) $applications->max_num_pages);
        echo '</div>';
    }

    private function get_applications_per_page(): int {
        $requested = isset($_GET['per_page']) ? absint($_GET['per_page']) : 0;

        if (in_array($requested, self::PER_PAGE_OPTIONS, true)) {
            update_user_meta(get_current_user_id(), self::PER_PAGE_USER_META, $requested);

            return $requested;
        }

        $saved = (int) get_user_meta(get_current_user_id(), self::PER_PAGE_USER_META, true);

        return in_array($saved, self::PER_PAGE_OPTIONS, true) ? $saved : self::DEFAULT_PER_PAGE;
    }

    private function render_per_page_selector(string $status, int $per_page): void {
        echo '<form method="get" class="db-per-page" style="margin:12px 0;">';
        echo '<input type="hidden" name="page" value="darmbulanz-survey">';
        echo '<input type="hidden" name="status" value="' . esc_attr($status) . '">';
        echo '<label for="db-per-page-select">Applications per page: </label>';
        echo '<select id="db-per-page-select" name="per_page" onchange="this.form.submit()">';
        foreach (self::PER_PAGE_OPTIONS as $option) {
            echo '<option value="' . esc_attr((string) $option) . '"' . selected($per_page, $option, false) . '>' . esc_html((string) $option) . '</option>';
        }
        echo '</select>';
        echo '<noscript><button type="submit" class="button">Apply</button></noscript>';
        echo '</form>';
    }

    private function render_pagination(string $status, int $per_page, int $current_page, int $total_pages): void {
        if ($total_pages < 2) {
            return;
        }

        $links = paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'current' => $current_page,
            'total' => $total_pages,
            'add_args' => [
                'page' => 'darmbulanz-survey',
                'status' => $status,
                'per_page' => $per_page,
            ],
        ]);

        if ($links) {
            echo '<div class="db-pagination" style="margin:16px 0;">' . $links . '</div>';
        }
    }

    private function render_admin_notice(): void {
        $notice = isset($_GET['db_notice']) ? sanitize_key((string) wp_unslash($_GET['db_notice'])) : '';
        $platform_notice = isset($_GET['platform_notice']) ? sanitize_key((string) wp_unslash($_GET['platform_notice'])) : '';
        $email_notice = isset($_GET['email_notice']) ? sanitize_key((string) wp_unslash($_GET['email_notice'])) : '';

        if ($notice === 'approved') {
            echo '<div class="notice notice-success is-dismissible"><p>Application approved.</p></div>';
        }
        if ($notice === 'rejected') {
            echo '<div class="notice notice-warning is-dismissible"><p>Application rejected.</p></div>';
        }
        if ($email_notice === 'sent') {
            echo '<div class="notice notice-success is-dismissible"><p>The applicant was notified by email.</p></div>';
        }
        if ($email_notice === 'failed') {
            echo '<div class="notice notice-error is-dismissible"><p>The application was rejected, but WordPress could not send the notification email.</p></div>';
        }
        if ($platform_notice === 'synced') {
            echo '<div class="notice notice-success is-dismissible"><p>Application was sent successfully to the Darmbulanz platform.</p></div>';
        }
        if ($platform_notice === 'not_configured') {
            echo '<div class="notice notice-info is-dismissible"><p>Application approved locally. Platform integration is not enabled or the token is missing.</p></div>';
        }
        if ($platform_notice === 'failed') {
            echo '<div class="notice notice-error is-dismissible"><p>Application approved locally, but sending it to the platform failed. Open the application details for the saved error and retry when ready.</p></div>';
        }
    }

    private function render_status_tabs(string $current): void {
        echo '<h2 class="nav-tab-wrapper">';
        foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $status => $label) {
            $class = $current === $status ? ' nav-tab-active' : '';
            $url = add_query_arg(['page' => 'darmbulanz-survey', 'status' => $status], admin_url('admin.php'));
            echo '<a class="nav-tab' . esc_attr($class) . '" href="' . esc_url($url) . '">' . esc_html($label . ' (' . $this->application_count($status) . ')') . '</a>';
        }
        echo '</h2>';
    }

    private function application_count(string $status): int {
        $query = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [
                ['key' => self::META_STATUS, 'value' => $status],
            ],
        ]);

        return (int) $query->found_posts;
    }

    private function render_admin_application_row(int $post_id): void {
        $fields = $this->get_application_fields($post_id);
        $status = $this->get_application_status($post_id);
        $name = trim(($fields['title'] !== '' ? (self::TITLES_EN[$fields['title']] ?? '') . ' ' : '') . $fields['first_name'] . ' ' . $fields['last_name']);
        $category_label = self::MAIN_CATEGORIES_EN[$fields['main_category']] ?? $fields['main_category'];
        $specialty_label = self::SPECIALTIES_BY_CATEGORY_EN[$fields['main_category']][$fields['specialty']] ?? $fields['specialty'];

        echo '<tr>';
        echo '<td><strong><a href="' . esc_url(get_edit_post_link($post_id, '')) . '">' . esc_html($name ?: get_the_title($post_id)) . '</a></strong></td>';
        echo '<td><a href="mailto:' . esc_attr($fields['email']) . '">' . esc_html($fields['email']) . '</a></td>';
        echo '<td>' . esc_html($category_label) . '<br><small>' . esc_html($specialty_label) . '</small></td>';
        echo '<td>' . $this->document_links_summary($post_id) . '</td>';
        echo '<td>' . esc_html($fields['submitted_at']) . '</td>';
        echo '<td>' . esc_html(ucfirst($status)) . '</td>';
        echo '<td>' . esc_html($this->format_platform_status($post_id)) . '</td>';
        echo '<td>';
        if ($status === 'pending') {
            $this->render_decision_form($post_id, 'approved', 'Approve', 'button-primary');
            $this->render_decision_form($post_id, 'rejected', 'Reject', 'button-secondary');
        } else {
            echo '<a class="button" href="' . esc_url(get_edit_post_link($post_id, '')) . '">View details</a>';
            if ($status === 'approved') {
                echo ' ';
                $this->render_platform_retry_form($post_id);
            }
        }
        echo '</td>';
        echo '</tr>';
    }

    private function render_decision_form(int $post_id, string $decision, string $label, string $button_class): void {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin:0 6px 6px 0;">';
        echo '<input type="hidden" name="action" value="db_application_decision">';
        echo '<input type="hidden" name="application_id" value="' . esc_attr((string) $post_id) . '">';
        echo '<input type="hidden" name="decision" value="' . esc_attr($decision) . '">';
        wp_nonce_field('db_application_decision_' . $post_id);
        echo '<button type="submit" class="button ' . esc_attr($button_class) . '">' . esc_html($label) . '</button>';
        echo '</form>';
    }

    private function render_platform_retry_form(int $post_id): void {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin:0 0 6px 0;">';
        echo '<input type="hidden" name="action" value="db_platform_retry">';
        echo '<input type="hidden" name="application_id" value="' . esc_attr((string) $post_id) . '">';
        wp_nonce_field('db_platform_retry_' . $post_id);
        echo '<button type="submit" class="button">Retry platform</button>';
        echo '</form>';
    }

    public function handle_application_decision(): void {
        $post_id = isset($_POST['application_id']) ? absint($_POST['application_id']) : 0;
        $decision = isset($_POST['decision']) ? sanitize_key((string) wp_unslash($_POST['decision'])) : '';

        if (!$post_id || !current_user_can(self::MANAGE_CAPABILITY) || !in_array($decision, ['approved', 'rejected'], true)) {
            wp_die(esc_html__('Invalid application decision.', 'darmbulanz-survey'));
        }

        check_admin_referer('db_application_decision_' . $post_id);

        update_post_meta($post_id, self::META_STATUS, $decision);
        update_post_meta($post_id, self::META_PREFIX . 'decided_at', current_time('mysql'));
        update_post_meta($post_id, self::META_PREFIX . 'decided_by', get_current_user_id());

        $platform_notice = '';
        if ($decision === 'approved') {
            $platform_notice = $this->sync_application_to_platform($post_id);
        }

        $email_notice = '';
        if ($decision === 'rejected') {
            $email_notice = $this->send_rejection_email($post_id) ? 'sent' : 'failed';
        }

        wp_safe_redirect(add_query_arg([
            'page' => 'darmbulanz-survey',
            'status' => $decision,
            'db_notice' => $decision,
            'platform_notice' => $platform_notice,
            'email_notice' => $email_notice,
        ], admin_url('admin.php')));
        exit;
    }

    private function send_rejection_email(int $post_id): bool {
        $fields = $this->get_application_fields($post_id);
        $recipient = sanitize_email($fields['email']);

        if (!is_email($recipient)) {
            update_post_meta($post_id, self::META_PREFIX . 'rejection_email_error', 'Missing or invalid recipient email.');
            return false;
        }

        $language = $this->is_supported_language($fields['language']) ? $fields['language'] : self::DEFAULT_LANGUAGE;
        $site_name = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
        $name = trim($fields['first_name'] . ' ' . $fields['last_name']);
        $replacements = [
            '{name}' => $name !== '' ? $name : $this->t_for_language('email.rejection.defaultName', $language),
            '{site}' => $site_name !== '' ? $site_name : 'Darmbulanz',
        ];
        $subject = strtr($this->t_for_language('email.rejection.subject', $language), $replacements);
        $body = strtr($this->t_for_language('email.rejection.body', $language), $replacements);
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: no-reply <' . self::REJECTION_EMAIL_FROM . '>',
        ];

        $sent = wp_mail($recipient, $subject, $body, $headers);

        if ($sent) {
            update_post_meta($post_id, self::META_PREFIX . 'rejection_email_sent_at', current_time('mysql'));
            update_post_meta($post_id, self::META_PREFIX . 'rejection_email_error', '');
        } else {
            update_post_meta($post_id, self::META_PREFIX . 'rejection_email_error', 'WordPress could not send the rejection email.');
        }

        return $sent;
    }

    public function handle_document_download(): void {
        $post_id = isset($_GET['application_id']) ? absint($_GET['application_id']) : 0;
        $attachment_id = isset($_GET['attachment_id']) ? absint($_GET['attachment_id']) : 0;

        if (!$post_id || !$attachment_id || !current_user_can(self::MANAGE_CAPABILITY)) {
            wp_die(esc_html__('You are not allowed to view this document.', 'darmbulanz-survey'), '', ['response' => 403]);
        }

        check_admin_referer('db_download_document_' . $post_id . '_' . $attachment_id);

        if (get_post_type($post_id) !== self::POST_TYPE || !$this->attachment_belongs_to_application($post_id, $attachment_id)) {
            wp_die(esc_html__('This document could not be found.', 'darmbulanz-survey'), '', ['response' => 404]);
        }

        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            wp_die(esc_html__('This document could not be found.', 'darmbulanz-survey'), '', ['response' => 404]);
        }

        $mime_type = get_post_mime_type($attachment_id) ?: 'application/octet-stream';

        nocache_headers();
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . (string) filesize($file_path));
        header('X-Content-Type-Options: nosniff');
        readfile($file_path);
        exit;
    }

    private function attachment_belongs_to_application(int $post_id, int $attachment_id): bool {
        $ids = get_post_meta($post_id, self::META_PREFIX . 'document_attachment_ids', true);
        $ids = is_array($ids) ? array_map('intval', $ids) : [];

        return in_array($attachment_id, $ids, true);
    }

    private function document_links_summary(int $post_id): string {
        $ids = get_post_meta($post_id, self::META_PREFIX . 'document_attachment_ids', true);
        $ids = is_array($ids) ? array_map('intval', $ids) : [];

        if ($ids === []) {
            return esc_html('No documents');
        }

        $links = [];
        foreach ($ids as $index => $attachment_id) {
            $links[] = $this->document_link($attachment_id, 'Document ' . ($index + 1), $post_id);
        }

        return implode('<br>', $links);
    }

    private function document_link(int $attachment_id, string $label, int $post_id): string {
        $url = wp_nonce_url(
            add_query_arg([
                'action' => self::DOWNLOAD_ACTION,
                'application_id' => $post_id,
                'attachment_id' => $attachment_id,
            ], admin_url('admin-post.php')),
            'db_download_document_' . $post_id . '_' . $attachment_id
        );

        return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($label) . '</a>';
    }

    public function register_application_meta_boxes(): void {
        add_meta_box(
            'db_application_details',
            'Application Details',
            [$this, 'render_application_details_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'db_application_status',
            'Review Status',
            [$this, 'render_application_status_meta_box'],
            self::POST_TYPE,
            'side',
            'high'
        );
    }

    public function render_application_details_meta_box(WP_Post $post): void {
        $fields = $this->get_application_fields($post->ID);
        $category_label = self::MAIN_CATEGORIES_EN[$fields['main_category']] ?? $fields['main_category'];
        $specialty_label = self::SPECIALTIES_BY_CATEGORY_EN[$fields['main_category']][$fields['specialty']] ?? $fields['specialty'];
        $title_label = $fields['title'] !== '' ? (self::TITLES_EN[$fields['title']] ?? $fields['title']) : 'Not provided';
        $salutation_label = self::SALUTATIONS_EN[$fields['salutation']] ?? $fields['salutation'];
        $country_label = self::COUNTRIES_EN[$fields['country']] ?? $fields['country'];

        echo '<table class="widefat striped"><tbody>';
        $this->render_detail_row('Salutation', $salutation_label);
        $this->render_detail_row('Title', $title_label);
        $this->render_detail_row('Name', trim($fields['first_name'] . ' ' . $fields['last_name']));
        $this->render_detail_row('Main category', $category_label);
        $this->render_detail_row('Specialty', $specialty_label);
        $this->render_detail_row('Additional qualifications', $fields['additional_qualifications'] !== '' ? $fields['additional_qualifications'] : 'Not provided');
        $this->render_detail_row('License number', $fields['license_number'] !== '' ? $fields['license_number'] : 'Not provided');
        $this->render_detail_row('Institution', $fields['institution']);
        $this->render_detail_row('Address', trim($fields['street'] . ', ' . $fields['postal_code'] . ' ' . $fields['city'] . ', ' . $country_label, ', '));
        $this->render_detail_row('Phone', $fields['phone']);
        $this->render_detail_row('Email', $fields['email']);
        $this->render_detail_row('Website', $fields['website'] !== '' ? $fields['website'] : 'Not provided');
        $this->render_detail_row('Social media', $fields['social_media'] !== '' ? $fields['social_media'] : 'Not provided');
        $this->render_detail_row('Focus areas', $fields['focus_areas'] !== '' ? $fields['focus_areas'] : 'Not provided');
        $this->render_detail_row('Interested in', $this->format_interest_summary($post->ID));
        $this->render_detail_row('Remarks', $fields['remarks'] !== '' ? $fields['remarks'] : 'Not provided');
        $this->render_detail_row('Language', strtoupper($fields['language']));
        $this->render_detail_row('Submitted', $fields['submitted_at']);
        echo '</tbody></table>';

        echo '<h3>Documents</h3>';
        echo '<p>' . $this->document_links_summary($post->ID) . '</p>';
    }

    private function format_interest_summary(int $post_id): string {
        $labels = [
            'interest_referral' => 'Patient referral',
            'interest_content' => 'Articles / content',
            'interest_training' => 'Trainings / talks',
            'interest_research' => 'Research cooperation',
        ];

        $selected = [];
        foreach ($labels as $meta_suffix => $label) {
            if (get_post_meta($post_id, self::META_PREFIX . $meta_suffix, true) === '1') {
                $selected[] = $label;
            }
        }

        return $selected === [] ? 'None selected' : implode(', ', $selected);
    }

    public function render_application_status_meta_box(WP_Post $post): void {
        $status = $this->get_application_status($post->ID);
        echo '<p><strong>Status:</strong> ' . esc_html(ucfirst($status)) . '</p>';
        if (get_post_meta($post->ID, self::META_PREFIX . 'resubmitted_after_rejection', true) === '1') {
            echo '<p style="color:#b45309;"><strong>&#9888; Resubmission:</strong><br>This applicant was rejected before and reapplied with the same email.</p>';
        }
        $decided_at = (string) get_post_meta($post->ID, self::META_PREFIX . 'decided_at', true);
        if ($decided_at !== '') {
            echo '<p><strong>Decided at:</strong><br>' . esc_html($decided_at) . '</p>';
        }
        $rejection_email_sent_at = (string) get_post_meta($post->ID, self::META_PREFIX . 'rejection_email_sent_at', true);
        $rejection_email_error = (string) get_post_meta($post->ID, self::META_PREFIX . 'rejection_email_error', true);
        if ($rejection_email_sent_at !== '') {
            echo '<p><strong>Rejection email:</strong><br>Sent at ' . esc_html($rejection_email_sent_at) . '</p>';
        } elseif ($rejection_email_error !== '') {
            echo '<p><strong>Rejection email:</strong><br>' . esc_html($rejection_email_error) . '</p>';
        }
        echo '<hr>';
        echo '<p><strong>Platform:</strong><br>' . esc_html($this->format_platform_status($post->ID)) . '</p>';
        $platform_synced_at = (string) get_post_meta($post->ID, self::META_PREFIX . 'platform_synced_at', true);
        if ($platform_synced_at !== '') {
            echo '<p><strong>Last sync:</strong><br>' . esc_html($platform_synced_at) . '</p>';
        }
        $platform_error = (string) get_post_meta($post->ID, self::META_PREFIX . 'platform_error', true);
        if ($platform_error !== '') {
            echo '<p><strong>Last error:</strong><br>' . esc_html($platform_error) . '</p>';
        }
    }

    private function render_detail_row(string $label, string $value): void {
        echo '<tr><th style="width:220px;">' . esc_html($label) . '</th><td>' . esc_html($value) . '</td></tr>';
    }

    public function filter_application_columns(array $columns): array {
        return [
            'cb' => $columns['cb'] ?? '',
            'title' => 'Application',
            'db_status' => 'Status',
            'db_email' => 'Email',
            'db_category' => 'Category',
            'db_platform' => 'Platform',
            'date' => $columns['date'] ?? 'Date',
        ];
    }

    public function render_application_column(string $column, int $post_id): void {
        if ($column === 'db_status') {
            echo esc_html(ucfirst($this->get_application_status($post_id)));
            return;
        }

        if ($column === 'db_email') {
            echo esc_html((string) get_post_meta($post_id, self::META_PREFIX . 'email', true));
            return;
        }

        if ($column === 'db_category') {
            $category = (string) get_post_meta($post_id, self::META_PREFIX . 'main_category', true);
            echo esc_html(self::MAIN_CATEGORIES_EN[$category] ?? $category);
            return;
        }

        if ($column === 'db_platform') {
            echo esc_html($this->format_platform_status($post_id));
        }
    }

    private function get_application_fields(int $post_id): array {
        $keys = [
            'salutation', 'title', 'first_name', 'last_name', 'main_category', 'specialty',
            'additional_qualifications', 'license_number', 'institution', 'street', 'postal_code',
            'city', 'country', 'phone', 'email', 'website', 'social_media', 'focus_areas',
            'remarks', 'language', 'submitted_at',
        ];
        $fields = [];
        foreach ($keys as $key) {
            $fields[$key] = (string) get_post_meta($post_id, self::META_PREFIX . $key, true);
        }

        return $fields;
    }

    private function format_platform_status(int $post_id): string {
        $status = (string) get_post_meta($post_id, self::META_PREFIX . 'platform_status', true);

        return match ($status) {
            'synced' => 'Synced',
            'failed' => 'Failed',
            'not_configured' => 'Not configured',
            default => 'Not sent',
        };
    }

    private function get_platform_settings(): array {
        $stored = get_option(self::PLATFORM_OPTION, []);
        $stored = is_array($stored) ? $stored : [];

        return [
            'enabled' => !empty($stored['enabled']) ? '1' : '0',
            'endpoint' => isset($stored['endpoint']) && is_string($stored['endpoint']) ? $stored['endpoint'] : '',
            'token' => isset($stored['token']) && is_string($stored['token']) ? $stored['token'] : '',
        ];
    }

    private function get_platform_token(array $settings): string {
        if (defined('DARMBULANZ_PLATFORM_TOKEN') && is_string(DARMBULANZ_PLATFORM_TOKEN) && DARMBULANZ_PLATFORM_TOKEN !== '') {
            return DARMBULANZ_PLATFORM_TOKEN;
        }

        return (string) ($settings['token'] ?? '');
    }

    private function get_platform_token_source(): string {
        if (defined('DARMBULANZ_PLATFORM_TOKEN') && is_string(DARMBULANZ_PLATFORM_TOKEN) && DARMBULANZ_PLATFORM_TOKEN !== '') {
            return 'wp-config.php';
        }

        return 'WordPress settings';
    }

    public function render_platform_settings_page(): void {
        if (!current_user_can(self::MANAGE_CAPABILITY)) {
            wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'darmbulanz-survey'));
        }

        $settings = $this->get_platform_settings();
        $token_source = $this->get_platform_token_source();
        $has_token = $this->get_platform_token($settings) !== '';

        echo '<div class="wrap db-admin">';
        echo '<h1>Darmbulanz Platform Settings</h1>';
        $this->render_platform_settings_notice();
        echo '<p>Configure this once you have the integration details for <strong>social.darmbulanz.net</strong> (endpoint URL and API token) from the Darmbulanz team. Approved applications are sent there automatically while this is enabled.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="db_save_platform_settings">';
        wp_nonce_field('db_platform_settings');
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row">Enable integration</th><td><label><input type="checkbox" name="platform_enabled" value="1" ' . checked($settings['enabled'], '1', false) . '> Send approved applications to social.darmbulanz.net</label></td></tr>';
        echo '<tr><th scope="row"><label for="platform_endpoint">API endpoint</label></th><td><input type="url" id="platform_endpoint" name="platform_endpoint" class="regular-text" value="' . esc_attr($settings['endpoint']) . '" placeholder="https://social.darmbulanz.net/api/..."></td></tr>';
        echo '<tr><th scope="row"><label for="platform_token">API token</label></th><td>';
        echo '<input type="password" id="platform_token" name="platform_token" class="regular-text" value="" autocomplete="new-password" placeholder="' . esc_attr($has_token ? 'Leave blank to keep the saved token' : 'Paste the platform API token') . '">';
        echo '<p class="description">' . esc_html($has_token ? 'A token is configured from ' . $token_source . '. It is never displayed here.' : 'No token is configured yet.') . '</p>';
        echo '</td></tr>';
        echo '</tbody></table>';
        echo '<p class="submit">';
        echo '<button type="submit" name="settings_mode" value="save" class="button button-primary">Save settings</button> ';
        echo '<button type="submit" name="settings_mode" value="test" class="button">Save and test connection</button>';
        echo '</p>';
        echo '</form>';
        echo '</div>';
    }

    public function handle_save_platform_settings(): void {
        if (!current_user_can(self::MANAGE_CAPABILITY)) {
            wp_die(esc_html__('You are not allowed to change these settings.', 'darmbulanz-survey'), '', ['response' => 403]);
        }

        check_admin_referer('db_platform_settings');

        $existing = $this->get_platform_settings();
        $endpoint = esc_url_raw((string) wp_unslash($_POST['platform_endpoint'] ?? ''));
        $token = sanitize_text_field((string) wp_unslash($_POST['platform_token'] ?? ''));
        $mode = sanitize_key((string) wp_unslash($_POST['settings_mode'] ?? 'save'));

        $settings = [
            'enabled' => !empty($_POST['platform_enabled']) ? '1' : '0',
            'endpoint' => $endpoint,
            'token' => $token !== '' ? $token : (string) ($existing['token'] ?? ''),
        ];

        update_option(self::PLATFORM_OPTION, $settings, false);

        if ($mode === 'test') {
            $result = $this->test_platform_connection($settings);
            $this->set_platform_settings_notice($result['ok'] ? 'success' : 'error', $result['message']);
        } else {
            $this->set_platform_settings_notice('success', 'Platform settings saved.');
        }

        wp_safe_redirect(add_query_arg(['page' => 'darmbulanz-survey-settings'], admin_url('admin.php')));
        exit;
    }

    public function handle_platform_retry(): void {
        $post_id = isset($_POST['application_id']) ? absint($_POST['application_id']) : 0;

        if (!$post_id || !current_user_can(self::MANAGE_CAPABILITY)) {
            wp_die(esc_html__('Invalid platform retry request.', 'darmbulanz-survey'));
        }

        check_admin_referer('db_platform_retry_' . $post_id);

        $platform_notice = $this->sync_application_to_platform($post_id);

        wp_safe_redirect(add_query_arg([
            'page' => 'darmbulanz-survey',
            'status' => $this->get_application_status($post_id),
            'platform_notice' => $platform_notice,
        ], admin_url('admin.php')));
        exit;
    }

    private function sync_application_to_platform(int $post_id): string {
        $settings = $this->get_platform_settings();
        $token = $this->get_platform_token($settings);

        if ($settings['enabled'] !== '1' || $token === '' || $settings['endpoint'] === '') {
            update_post_meta($post_id, self::META_PREFIX . 'platform_status', 'not_configured');
            update_post_meta($post_id, self::META_PREFIX . 'platform_error', 'Platform integration is disabled or no endpoint/token is configured.');
            return 'not_configured';
        }

        $fields = $this->get_application_fields($post_id);
        if (!is_email($fields['email'])) {
            update_post_meta($post_id, self::META_PREFIX . 'platform_status', 'failed');
            update_post_meta($post_id, self::META_PREFIX . 'platform_error', 'Cannot sync because the application email is invalid.');
            return 'failed';
        }

        // NOTE: payload shape is a reasonable placeholder until the real
        // social.darmbulanz.net API contract is confirmed by the Darmbulanz team.
        $payload = [
            'email' => $fields['email'],
            'firstName' => $fields['first_name'],
            'lastName' => $fields['last_name'],
            'mainCategory' => self::MAIN_CATEGORIES_EN[$fields['main_category']] ?? $fields['main_category'],
            'specialty' => self::SPECIALTIES_BY_CATEGORY_EN[$fields['main_category']][$fields['specialty']] ?? $fields['specialty'],
            'institution' => $fields['institution'],
            'locale' => $this->is_supported_language($fields['language']) ? $fields['language'] : self::DEFAULT_LANGUAGE,
            'externalReference' => 'darmbulanz-wp-' . $post_id,
        ];

        $result = $this->call_platform_api($settings['endpoint'], $token, $payload);

        update_post_meta($post_id, self::META_PREFIX . 'platform_synced_at', current_time('mysql'));
        update_post_meta($post_id, self::META_PREFIX . 'platform_response', wp_json_encode($result['response'] ?? []));

        if (!$result['ok']) {
            update_post_meta($post_id, self::META_PREFIX . 'platform_status', 'failed');
            update_post_meta($post_id, self::META_PREFIX . 'platform_error', $result['message']);
            return 'failed';
        }

        update_post_meta($post_id, self::META_PREFIX . 'platform_status', 'synced');
        update_post_meta($post_id, self::META_PREFIX . 'platform_error', '');
        return 'synced';
    }

    private function test_platform_connection(array $settings): array {
        $token = $this->get_platform_token($settings);
        if ($token === '' || $settings['endpoint'] === '') {
            return [
                'ok' => false,
                'message' => 'Settings saved, but the endpoint or API token is not configured yet.',
            ];
        }

        return $this->call_platform_api($settings['endpoint'], $token, ['ping' => true]);
    }

    private function call_platform_api(string $endpoint, string $token, array $payload): array {
        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return [
                'ok' => false,
                'message' => $response->get_error_message(),
                'response' => [],
            ];
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        $decoded = is_array($decoded) ? $decoded : [];

        if ($status_code < 200 || $status_code >= 300) {
            return [
                'ok' => false,
                'message' => 'Platform returned HTTP ' . $status_code . '.',
                'response' => $decoded,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Connection to the platform is working.',
            'response' => $decoded,
        ];
    }

    private function set_platform_settings_notice(string $type, string $message): void {
        set_transient(
            'db_platform_settings_notice_' . get_current_user_id(),
            ['type' => $type, 'message' => $message],
            MINUTE_IN_SECONDS
        );
    }

    private function render_platform_settings_notice(): void {
        $notice = get_transient('db_platform_settings_notice_' . get_current_user_id());
        if (!is_array($notice) || empty($notice['message'])) {
            return;
        }

        delete_transient('db_platform_settings_notice_' . get_current_user_id());
        $type = (string) ($notice['type'] ?? 'success');
        $class = $type === 'error' ? 'notice notice-error is-dismissible' : 'notice notice-success is-dismissible';

        echo '<div class="' . esc_attr($class) . '"><p>' . esc_html((string) $notice['message']) . '</p></div>';
    }

    public function render_privacy_policy_page(): void {
        if (!current_user_can(self::MANAGE_CAPABILITY)) {
            return;
        }

        $current_url = (string) get_option(self::PRIVACY_POLICY_OPTION, '');
        ?>
        <div class="wrap">
            <h1>Privacy Policy Link</h1>
            <?php if (isset($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Privacy policy link saved.</p></div>
            <?php endif; ?>
            <p>This is the link shown next to the consent checkbox on the registration form. Until you set it below, that link points to <code>#</code> instead of a real page.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="db_save_privacy_policy">
                <?php wp_nonce_field('db_privacy_policy'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="db_privacy_policy_url">Privacy policy URL</label></th>
                            <td>
                                <input type="url" id="db_privacy_policy_url" name="privacy_policy_url" class="regular-text" value="<?php echo esc_attr($current_url); ?>" placeholder="https://darmbulanz.net/datenschutz">
                                <p class="description">Leave blank to fall back to <code>#</code>.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit"><button type="submit" class="button button-primary">Save</button></p>
            </form>
        </div>
        <?php
    }

    public function handle_save_privacy_policy(): void {
        if (!current_user_can(self::MANAGE_CAPABILITY)) {
            wp_die(esc_html__('You are not allowed to change this setting.', 'darmbulanz-survey'), '', ['response' => 403]);
        }

        check_admin_referer('db_privacy_policy');

        $url = esc_url_raw((string) wp_unslash($_POST['privacy_policy_url'] ?? ''));
        update_option(self::PRIVACY_POLICY_OPTION, $url, false);

        wp_safe_redirect(add_query_arg(['page' => 'db_privacy_policy', 'updated' => '1'], admin_url('admin.php')));
        exit;
    }

    public function render_help_page(): void {
        if (!current_user_can(self::MANAGE_CAPABILITY)) {
            return;
        }

        $shortcode = '[' . self::SHORTCODE . ']';
        ?>
        <div class="wrap">
            <h1>Darmbulanz Survey — Help</h1>

            <h2>What this plugin does</h2>
            <p>Displays a registration form for the Darmbulanz professional network (Fachkreise). Every submission becomes an Application in this dashboard, awaiting your approval or rejection.</p>

            <h2>Adding the form to a page</h2>
            <p>Add this shortcode to any page or post:</p>
            <p><code><?php echo esc_html($shortcode); ?></code></p>

            <h2>Reviewing applications</h2>
            <ul>
                <li>Go to <strong>Darmbulanz Survey → Applications</strong> to see submissions grouped by Pending / Approved / Rejected.</li>
                <li>Each row shows the applicant's uploaded documents (license, certificates, ID) for review before you decide.</li>
                <li><strong>Approve</strong> or <strong>Reject</strong> a pending application directly from the list, or open it for full details first.</li>
                <li>Rejected applicants receive an automatic notification email. Approved applicants are sent to social.darmbulanz.net if that integration is enabled.</li>
                <li>Someone rejected once can reapply with the same email; the new application is flagged as a resubmission.</li>
            </ul>

            <h2>Platform integration</h2>
            <p>Go to <strong>Darmbulanz Survey → Platform Settings</strong> to configure the connection to social.darmbulanz.net once you have the endpoint and API token.</p>

            <h2>Languages</h2>
            <p>The form supports German and English. Visitors can switch language from the form itself; their choice is remembered for future visits. <strong>English copy is currently a placeholder</strong> pending the official translation.</p>
        </div>
        <?php
    }
}

register_activation_hook(__FILE__, [Darmbulanz_Survey::class, 'activate']);
new Darmbulanz_Survey();
