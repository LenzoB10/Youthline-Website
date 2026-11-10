<?php

namespace Database\Seeders;

use App\Models\AcademicResult;
use App\Models\AcademicYear;
use App\Models\AlumniOutcome;
use App\Models\Application;
use App\Models\Business;
use App\Models\BusinessCheckin;
use App\Models\CareerInterest;
use App\Models\Consent;
use App\Models\ContactInfo;
use App\Models\ContactMessage;
use App\Models\CoreValue;
use App\Models\Distribution;
use App\Models\Document;
use App\Models\Donation;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\HomeVisit;
use App\Models\Household;
use App\Models\Institution;
use App\Models\InvolvementRequest;
use App\Models\ItemCategory;
use App\Models\LoanRepayment;
use App\Models\Mentor;
use App\Models\MentorAssignment;
use App\Models\MentorshipSession;
use App\Models\NewsletterSubscriber;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Program;
use App\Models\RepairProject;
use App\Models\SiteSetting;
use App\Models\Sponsor;
use App\Models\Sponsorship;
use App\Models\Student;
use App\Models\SubjectGrade;
use App\Models\TeamMember;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class YouthlineSeeder extends Seeder
{
    protected User $admin;

    protected User $manager;

    public function run(): void
    {
        $this->seedUsers();
        $this->seedWebsiteContent();
        $this->seedReferenceData();
        $this->seedStudents();
        $this->seedPublicSubmissions();
    }

    // ---------------------------------------------------------------
    // Users & roles
    // ---------------------------------------------------------------
    protected function seedUsers(): void
    {
        $this->admin = User::firstOrCreate(
            ['email' => 'promisemphoola2@gmail.com'],
            ['name' => 'Promis Mphoola', 'password' => Hash::make('1234567890'), 'status' => 'active']
        );

        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $managerRole->givePermissionTo('manage website content');

        $this->manager = User::firstOrCreate(
            ['email' => 'manager@youthline.org'],
            ['name' => 'Grace Banda', 'password' => Hash::make('password'), 'status' => 'active']
        );
        $this->manager->assignRole('Manager');
    }

    // ---------------------------------------------------------------
    // Public website content (D8)
    // ---------------------------------------------------------------
    protected function seedWebsiteContent(): void
    {
        SiteSetting::create([
            'org_name' => 'Youthline',
            'tagline' => 'Empowering Young People. Transforming Lives. Building Futures.',
            'footer_text' => '© '.date('Y').' Youthline. Empowering Young People. Transforming Lives. Building Futures.',
            'social_links' => [
                'facebook' => 'https://facebook.com/youthline',
                'instagram' => 'https://instagram.com/youthline',
                'x' => 'https://x.com/youthline',
            ],
        ]);

        ContactInfo::create([
            'phone' => '+265 999 000 000',
            'email' => 'info@youthline.org',
            'address' => 'Blantyre, Malawi',
            'office_hours' => 'Monday – Friday, 8:00am – 5:00pm',
            'map_location' => '-15.7861,35.0058',
        ]);

        $values = [
            ['Compassion', 'Treating every young person with kindness, dignity, and understanding.'],
            ['Empowerment', 'Equipping young people with the tools and opportunities to shape their futures.'],
            ['Integrity', 'Being transparent, accountable, and responsible in everything we do.'],
            ['Equity', 'Promoting opportunities for young people regardless of their financial circumstances.'],
            ['Resilience', 'Encouraging young people to overcome challenges and recognize their potential.'],
            ['Community', 'Recognizing that sustainable change requires families and communities to work together.'],
        ];
        foreach ($values as $i => [$name, $desc]) {
            CoreValue::create(['name' => $name, 'description' => $desc, 'display_order' => $i + 1]);
        }

        $aboutBody = implode("\n\n", [
            'Youthline is a youth empowerment and community development organization committed to improving the lives and futures of vulnerable and underserved young people.',
            'We believe that every young person deserves an opportunity to succeed, regardless of their financial circumstances or background. However, access to education alone is not always enough. Many young people face challenges such as poverty, inadequate housing, limited access to basic necessities, lack of mentorship, and limited economic opportunities.',
            'At Youthline, we take a holistic approach to youth empowerment. We go beyond providing financial assistance by addressing the social, educational, emotional, and economic challenges that affect young people’s ability to thrive.',
            'We believe that empowering one young person can transform an entire family and create a ripple effect throughout a community.',
        ]);

        $pages = [
            ['home', 'Empowering Young People. Transforming Lives. Building Futures.', 'Youthline helps vulnerable young people access education, essential resources, mentorship and opportunity.', 'Youthline helps vulnerable young people access education, essential resources, mentorship and opportunity.'],
            ['about', 'About Youthline', 'A youth empowerment and community development organization improving the lives of vulnerable and underserved young people.', $aboutBody],
            ['vision', 'Our Vision', 'Where we are headed.', 'To create a society where every young person, regardless of their socioeconomic background, has the opportunity, resources, and support needed to achieve their full potential, become self-reliant, and contribute meaningfully to their communities.'],
            ['mission', 'Our Mission', 'What we do every day.', 'To empower vulnerable and underserved young people through access to education, essential resources, mentorship, psychosocial support, career guidance, and economic opportunities — addressing the barriers that prevent young people from reaching their potential while strengthening families and communities to create lasting, sustainable change.'],
            ['commitment', 'Our Commitment', 'We believe every young person deserves a chance to thrive.', 'We invest in education, nurture potential, strengthen families, and create opportunities for young people to become independent, confident, and productive members of society. Youthline does not just invest in students; it invests in the circumstances that make student success possible.'],
            ['programs', 'Our Programs', 'Six connected programs that support a young person’s whole journey.', 'From the classroom to the home to the start of a career, our six programs work together to support the whole person.'],
            ['impact', 'Our Impact', 'The difference Youthline makes, measured from real records.', 'Every number here is counted live from our records — never names, only the reach of the work.'],
            ['get_involved', 'Get Involved', 'Volunteer, mentor, partner, sponsor a student, or donate.', 'There are many ways to help a young person thrive.'],
            ['news', 'News and Stories', 'Updates, success stories and activities from Youthline.', 'Updates, success stories and activities from Youthline and the young people we serve.'],
            ['contact', 'Contact Us', 'Get in touch with the Youthline team.', 'We would love to hear from you.'],
        ];
        foreach ($pages as [$key, $title, $summary, $body]) {
            Page::create([
                'key' => $key,
                'title' => $title,
                'summary' => $summary,
                'body' => $body,
                'updated_by' => $this->admin->id,
            ]);
        }

        $team = [
            ['Chikondi Banda', 'Executive Director', 'Leads Youthline’s vision and partnerships across Malawi.'],
            ['Alinafe Phiri', 'Programs Manager', 'Oversees the six programs and the day-to-day support of students.'],
            ['Thoko Mbewe', 'Community & Family Lead', 'Coordinates home visits, family assessments and repairs.'],
        ];
        foreach ($team as $i => [$name, $position, $bio]) {
            TeamMember::create([
                'name' => $name,
                'position' => $position,
                'bio' => $bio,
                'display_order' => $i + 1,
            ]);
        }
    }

    // ---------------------------------------------------------------
    // Reference data (D1) + mentors + sponsors
    // ---------------------------------------------------------------
    protected array $institutions = [];

    protected array $years = [];

    protected array $terms = [];   // [yearName => [Term,...]]

    protected array $programs = [];

    protected array $itemCategories = [];

    protected array $mentors = [];

    protected function seedReferenceData(): void
    {
        foreach ([
            ['Blantyre Secondary School', 'secondary', 'Blantyre'],
            ['Ndirande Community Day Secondary', 'secondary', 'Blantyre'],
            ['Zomba Catholic Secondary', 'secondary', 'Zomba'],
            ['Malawi University of Science and Technology', 'university', 'Thyolo'],
            ['University of Malawi', 'university', 'Zomba'],
        ] as [$name, $type, $district]) {
            $this->institutions[] = Institution::create([
                'name' => $name, 'type' => $type, 'district' => $district, 'phone' => fake()->phoneNumber(),
            ]);
        }

        foreach (['2024' => false, '2025' => true] as $name => $current) {
            $year = AcademicYear::create([
                'name' => $name,
                'start_date' => "$name-01-01",
                'end_date' => "$name-12-15",
                'is_current' => $current,
            ]);
            $this->years[$name] = $year;
            $this->terms[$name] = [];
            foreach (['Term 1', 'Term 2', 'Term 3'] as $t => $tname) {
                $this->terms[$name][] = Term::create([
                    'academic_year_id' => $year->id,
                    'name' => $tname,
                    'start_date' => "$name-".str_pad((string) ($t * 4 + 1), 2, '0', STR_PAD_LEFT).'-01',
                    'end_date' => "$name-".str_pad((string) ($t * 4 + 3), 2, '0', STR_PAD_LEFT).'-30',
                ]);
            }
        }

        $programDefs = [
            ['Educational Support and Scholarships', 'Tuition assistance and educational support for financially disadvantaged students.'],
            ['Basic Needs and Essential Support', 'Essential supplies and basic necessities for young people in hardship.'],
            ['Mentorship and Psychosocial Support', 'Guidance, counseling and mentorship to build resilience and confidence.'],
            ['Career Development and Guidance', 'Helping young people explore careers and prepare for employment.'],
            ['Family and Community Support', 'Home visits, family assessments and essential home repairs.'],
            ['Youth Entrepreneurship and Economic Empowerment', 'Startup assistance to help young people build small businesses.'],
        ];
        foreach ($programDefs as $i => [$name, $desc]) {
            $this->programs[] = Program::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'summary' => $desc,
                'description' => $desc,
                'display_order' => $i + 1,
            ]);
        }

        foreach ([
            ['Uniforms', 8500], ['Exercise books', 300], ['School bags', 6000],
            ['Sanitary pads', 1500], ['Food packs', 12000], ['Shoes', 9000],
        ] as [$name, $cost]) {
            $this->itemCategories[] = ItemCategory::create(['name' => $name, 'unit_cost' => $cost]);
        }

        foreach ([
            ['Chimwemwe Phiri', 'mentorship'], ['John Gondwe', 'mentorship'],
            ['Esther Mwale', 'counselling'], ['Daniel Chirwa', 'counselling'],
        ] as [$name, $_]) {
            $this->mentors[] = Mentor::create([
                'name' => $name, 'phone' => fake()->phoneNumber(),
                'email' => Str::slug($name).'@mentors.youthline.org', 'is_active' => true,
            ]);
        }
    }

    // ---------------------------------------------------------------
    // Students and all their related records (D2–D7)
    // ---------------------------------------------------------------
    protected function seedStudents(): void
    {
        // [first, last, gender, status, level2024, level2025, exitReason]
        $defs = [
            ['Thandiwe', 'Banda', 'female', 'active', 'form_2', 'form_3', null],
            ['Limbani', 'Moyo', 'male', 'active', 'form_3', 'form_4', null],
            ['Chisomo', 'Nkhoma', 'female', 'active', 'form_1', 'form_2', null],
            ['Mphatso', 'Kumwenda', 'male', 'active', 'form_4', 'year_1', null], // moved to university
            ['Tadala', 'Chimpeni', 'female', 'graduated', 'year_2', 'year_3', null],
            ['Yamikani', 'Zimba', 'male', 'dropped_out', 'form_2', 'form_2', 'Family relocated'],
            ['Pemphero', 'Gama', 'female', 'applicant', null, null, null],
            ['Blessings', 'Tembo', 'male', 'active', 'form_1', 'form_2', null],
        ];

        $districts = ['Blantyre', 'Zomba', 'Thyolo', 'Chiradzulu'];

        foreach ($defs as $i => [$first, $last, $gender, $status, $lvl24, $lvl25, $exit]) {
            $household = Household::create([
                'head_name' => fake()->name(),
                'village_area' => fake()->streetName(),
                'district' => $districts[$i % count($districts)],
                'phone' => fake()->phoneNumber(),
                'members_count' => rand(3, 8),
            ]);

            $student = Student::create([
                'household_id' => $household->id,
                'first_name' => $first,
                'last_name' => $last,
                'date_of_birth' => fake()->dateTimeBetween('-20 years', '-14 years')->format('Y-m-d'),
                'gender' => $gender,
                'phone' => fake()->phoneNumber(),
                'home_location' => $household->village_area.', '.$household->district,
                'guardian_name' => $household->head_name,
                'guardian_phone' => $household->phone,
                'guardian_relationship' => fake()->randomElement(['Mother', 'Father', 'Aunt', 'Grandmother', 'Uncle']),
                'status' => $status,
                'exit_reason' => $exit,
                'exit_date' => $exit ? '2025-03-15' : null,
                'created_by' => $this->manager->id,
            ]);

            // Documents + consent
            $birthCert = Document::create([
                'student_id' => $student->id, 'type' => 'birth_cert',
                'file_path' => "documents/{$student->id}/birth_certificate.pdf",
                'uploaded_by' => $this->manager->id, 'uploaded_at' => now(),
            ]);
            $consentDoc = Document::create([
                'student_id' => $student->id, 'type' => 'consent',
                'file_path' => "documents/{$student->id}/consent_form.pdf",
                'uploaded_by' => $this->manager->id, 'uploaded_at' => now(),
            ]);
            Consent::create([
                'student_id' => $student->id,
                'photo_allowed' => $i % 2 === 0,
                'story_allowed' => $i % 3 === 0,
                'signed_on' => '2024-01-20',
                'document_id' => $consentDoc->id,
            ]);

            // Applicant: assessment only, no enrolment yet
            if ($status === 'applicant') {
                HomeVisit::create([
                    'household_id' => $household->id,
                    'visit_date' => now()->subDays(10),
                    'living_conditions' => 'Assessment visit for new applicant.',
                    'income_sources' => 'Small-scale farming',
                    'needs_identified' => 'School fees, uniform',
                    'next_visit_due' => now()->addMonths(2),
                    'recorded_by' => $this->manager->id,
                ]);

                continue;
            }

            $institution = $this->institutions[$i % 3]; // a secondary school
            $university = $this->institutions[3 + ($i % 2)];

            // Enrolment history: one row per year (never overwritten)
            $enr24 = null;
            if ($lvl24) {
                $enr24 = Enrollment::create([
                    'student_id' => $student->id,
                    'institution_id' => Str::startsWith($lvl24, 'year') ? $university->id : $institution->id,
                    'academic_year_id' => $this->years['2024']->id,
                    'level' => $lvl24,
                    'is_repeating' => false,
                ]);
            }
            $enr25 = null;
            if ($lvl25) {
                $enr25 = Enrollment::create([
                    'student_id' => $student->id,
                    'institution_id' => Str::startsWith($lvl25, 'year') ? $university->id : $institution->id,
                    'academic_year_id' => $this->years['2025']->id,
                    'level' => $lvl25,
                    'is_repeating' => $lvl24 === $lvl25,
                ]);
            }
            $currentEnr = $enr25 ?? $enr24;

            // Programs: everyone gets Education + a couple of others
            $student->programs()->attach([
                $this->programs[0]->id => ['start_date' => '2024-01-15'],
                $this->programs[1]->id => ['start_date' => '2024-01-15'],
                $this->programs[2]->id => ['start_date' => '2024-01-15'],
            ]);

            // Academic results for 2025 terms (with a grade drop on student 1)
            if ($currentEnr && ! Str::startsWith((string) $currentEnr->level, 'year')) {
                $averages = $i === 0 ? [72, 68, 54] : [fake()->numberBetween(55, 85), fake()->numberBetween(55, 85), fake()->numberBetween(55, 85)];
                foreach ($this->terms['2025'] as $t => $term) {
                    $report = Document::create([
                        'student_id' => $student->id, 'type' => 'report_card',
                        'file_path' => "documents/{$student->id}/report_{$term->id}.pdf",
                        'uploaded_by' => $this->manager->id, 'uploaded_at' => now(),
                    ]);
                    $result = AcademicResult::create([
                        'student_id' => $student->id,
                        'enrollment_id' => $currentEnr->id,
                        'term_id' => $term->id,
                        'exam_type' => 'term',
                        'average' => $averages[$t],
                        'position' => fake()->numberBetween(1, 40).' of 40',
                        'report_card_document_id' => $report->id,
                        'recorded_by' => $this->manager->id,
                    ]);
                    foreach (['English', 'Mathematics', 'Biology', 'Chichewa'] as $subject) {
                        SubjectGrade::create([
                            'result_id' => $result->id,
                            'subject' => $subject,
                            'grade' => fake()->randomElement(['A', 'B', 'C', 'D']),
                        ]);
                    }
                }
            }

            // Fees + payments for 2025 terms
            foreach ($this->terms['2025'] as $t => $term) {
                $owed = 45000;
                $fee = Fee::create([
                    'student_id' => $student->id,
                    'term_id' => $term->id,
                    'institution_id' => $currentEnr?->institution_id ?? $institution->id,
                    'amount_owed' => $owed,
                    'youthline_share' => $owed,
                ]);
                // Pay terms 1 & 2; term 3 left unpaid to populate the "unpaid" list
                if ($t < 2) {
                    $receipt = Document::create([
                        'student_id' => $student->id, 'type' => 'receipt',
                        'file_path' => "documents/{$student->id}/receipt_{$fee->id}.pdf",
                        'uploaded_by' => $this->manager->id, 'uploaded_at' => now(),
                    ]);
                    $big = $owed > 40000;
                    Payment::create([
                        'fee_id' => $fee->id,
                        'amount' => $owed,
                        'paid_on' => $term->start_date,
                        'method' => fake()->randomElement(['Bank transfer', 'Cheque', 'Cash']),
                        'reference' => 'PMT-'.strtoupper(Str::random(8)),
                        'receipt_document_id' => $receipt->id,
                        // over-limit payments start pending_approval and record the approver
                        'status' => $big ? 'paid' : 'paid',
                        'recorded_by' => $this->manager->id,
                        'approved_by' => $big ? $this->admin->id : null,
                    ]);
                }
            }

            // Basic needs distributions
            Distribution::create([
                'student_id' => $student->id,
                'item_category_id' => $this->itemCategories[0]->id,
                'quantity' => 2, 'given_on' => '2025-01-10', 'recorded_by' => $this->manager->id,
            ]);
            Distribution::create([
                'student_id' => $student->id,
                'item_category_id' => $this->itemCategories[1]->id,
                'quantity' => 6, 'given_on' => '2025-01-10', 'recorded_by' => $this->manager->id,
            ]);

            // Mentorship
            $mentor = $this->mentors[$i % count($this->mentors)];
            MentorAssignment::create([
                'student_id' => $student->id, 'mentor_id' => $mentor->id, 'start_date' => '2024-02-01',
            ]);
            MentorshipSession::create([
                'student_id' => $student->id, 'mentor_id' => $mentor->id,
                'session_date' => now()->subWeeks(3),
                'type' => fake()->randomElement(['mentorship', 'counselling']),
                'notes' => 'Discussed academic goals and home situation. (restricted)',
                'follow_up_needed' => $i % 2 === 0,
                'recorded_by' => $this->manager->id,
            ]);

            // Family: home visit + a repair project on some households
            HomeVisit::create([
                'household_id' => $household->id,
                'visit_date' => now()->subMonths(2),
                'living_conditions' => fake()->sentence(),
                'income_sources' => fake()->randomElement(['Farming', 'Small business', 'Casual labour']),
                'needs_identified' => fake()->randomElement(['Roof repair', 'Toilet', 'None urgent']),
                'next_visit_due' => now()->addMonths(10),
                'recorded_by' => $this->manager->id,
            ]);
            if ($i % 3 === 0) {
                RepairProject::create([
                    'household_id' => $household->id,
                    'description' => 'Roof repair and new door.',
                    'estimated_cost' => 120000,
                    'actual_cost' => $i === 0 ? 115000 : null,
                    'status' => $i === 0 ? 'completed' : 'planned',
                    'approved_by' => $this->admin->id,
                ]);
            }

            // Entrepreneurship for a couple of older students
            if (in_array($status, ['graduated', 'active']) && $i % 4 === 1) {
                $business = Business::create([
                    'student_id' => $student->id,
                    'name' => fake()->randomElement(['Tailoring', 'Poultry', 'Grocery stall']),
                    'business_type' => 'Retail',
                    'start_date' => '2024-06-01',
                    'amount_given' => 80000,
                    'funding_type' => 'loan',
                    'is_running' => true,
                ]);
                BusinessCheckin::create([
                    'business_id' => $business->id, 'checkin_date' => now()->subMonth(),
                    'monthly_income' => 35000, 'notes' => 'Business running well.',
                    'recorded_by' => $this->manager->id,
                ]);
                LoanRepayment::create(['business_id' => $business->id, 'amount' => 20000, 'paid_on' => now()->subMonths(2)]);
                LoanRepayment::create(['business_id' => $business->id, 'amount' => 20000, 'paid_on' => now()->subMonth()]);
            }

            // Careers & applications
            CareerInterest::create(['student_id' => $student->id, 'interest' => fake()->randomElement(['Medicine', 'Engineering', 'Teaching', 'Business'])]);
            if (in_array($lvl25, ['form_4', 'year_1']) || $status === 'graduated') {
                Application::create([
                    'student_id' => $student->id,
                    'type' => fake()->randomElement(['university', 'scholarship']),
                    'organisation' => $university->name,
                    'applied_on' => '2025-02-01',
                    'status' => fake()->randomElement(['applied', 'accepted', 'rejected']),
                ]);
            }

            // Graduated: alumni outcome
            if ($status === 'graduated') {
                AlumniOutcome::create([
                    'student_id' => $student->id,
                    'recorded_on' => now()->subMonths(1),
                    'outcome' => fake()->randomElement(['employed', 'further_study', 'business']),
                    'details' => 'Followed up after graduation.',
                ]);
            }
        }

        $this->seedSponsorsAndDonations();
    }

    protected function seedSponsorsAndDonations(): void
    {
        $students = Student::where('status', 'active')->get();

        $sponsors = [];
        foreach ([
            ['Helping Hands Foundation', 'organisation'],
            ['Mary Johnson', 'person'],
            ['Lilongwe Rotary Club', 'organisation'],
        ] as [$name, $type]) {
            $sponsors[] = Sponsor::create([
                'name' => $name, 'type' => $type,
                'email' => Str::slug($name).'@example.com', 'phone' => fake()->phoneNumber(),
                'stripe_customer_id' => 'cus_'.Str::random(14),
            ]);
        }

        foreach ($students as $idx => $student) {
            $sponsor = $sponsors[$idx % count($sponsors)];
            Sponsorship::create([
                'sponsor_id' => $sponsor->id,
                'student_id' => $student->id,
                'start_date' => '2024-01-01',
                'amount_per_year' => 150000,
            ]);
        }

        // Donations via Stripe (references only, no card data)
        foreach ($sponsors as $sponsor) {
            Donation::create([
                'sponsor_id' => $sponsor->id,
                'donor_name' => $sponsor->name,
                'donor_email' => $sponsor->email,
                'is_anonymous' => false,
                'amount' => fake()->randomElement([50, 100, 250]),
                'currency' => 'USD',
                'type' => fake()->randomElement(['one_time', 'monthly']),
                'cause' => fake()->randomElement(['general', 'program', 'sponsor_student']),
                'program_id' => $this->programs[0]->id,
                'status' => 'paid',
                'stripe_session_id' => 'cs_'.Str::random(20),
                'stripe_payment_intent_id' => 'pi_'.Str::random(20),
                'paid_at' => now()->subDays(rand(1, 60)),
            ]);
        }

        // An anonymous one-time donation with no sponsor link
        Donation::create([
            'donor_name' => 'Anonymous',
            'is_anonymous' => true,
            'amount' => 75, 'currency' => 'USD', 'type' => 'one_time', 'cause' => 'general',
            'status' => 'paid', 'stripe_session_id' => 'cs_'.Str::random(20),
            'stripe_payment_intent_id' => 'pi_'.Str::random(20), 'paid_at' => now()->subDays(5),
        ]);
    }

    // ---------------------------------------------------------------
    // Public website form submissions (D8)
    // ---------------------------------------------------------------
    protected function seedPublicSubmissions(): void
    {
        ContactMessage::create([
            'name' => 'James Phiri', 'email' => 'james@example.com', 'phone' => fake()->phoneNumber(),
            'message' => 'I would like to know more about volunteering.', 'status' => 'new',
        ]);
        ContactMessage::create([
            'name' => 'Linda Zulu', 'email' => 'linda@example.com', 'phone' => fake()->phoneNumber(),
            'message' => 'How can my company partner with Youthline?', 'status' => 'replied',
        ]);

        InvolvementRequest::create([
            'name' => 'Peter Mvula', 'email' => 'peter@example.com', 'phone' => fake()->phoneNumber(),
            'type' => 'mentor', 'message' => 'I am a teacher and would like to mentor students.', 'status' => 'new',
        ]);
        InvolvementRequest::create([
            'name' => 'Susan Kaunda', 'email' => 'susan@example.com', 'phone' => fake()->phoneNumber(),
            'type' => 'sponsor', 'message' => 'I would like to sponsor a student.', 'status' => 'contacted',
        ]);

        foreach (['subscriber1@example.com', 'subscriber2@example.com', 'subscriber3@example.com'] as $email) {
            NewsletterSubscriber::create(['email' => $email, 'subscribed_at' => now()->subDays(rand(1, 90))]);
        }

        // News & stories (a success story only published where consent allows)
        $consented = Student::whereHas('consent', fn ($q) => $q->where('story_allowed', true))->first();
        NewsPost::create([
            'title' => 'Youthline reaches 100 students this year',
            'category' => 'news', 'body' => 'A milestone for the organisation as support expands.',
            'is_published' => true, 'published_at' => now()->subWeeks(2), 'author_id' => $this->admin->id,
        ]);
        NewsPost::create([
            'title' => 'Term 1 uniform distribution',
            'category' => 'activity', 'body' => 'Uniforms and books handed out ahead of the new term.',
            'is_published' => true, 'published_at' => now()->subWeeks(5), 'author_id' => $this->admin->id,
        ]);
        NewsPost::create([
            'title' => 'A student success story',
            'category' => 'success_story', 'body' => 'From scholarship to university — a journey of resilience.',
            'student_id' => $consented?->id,
            'is_published' => (bool) $consented, // only publish if consent on file
            'published_at' => $consented ? now()->subWeek() : null,
            'author_id' => $this->admin->id,
        ]);
    }
}
