<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Hr\VacancyRequisition;
use App\Models\Hr\Applicant;
use App\Models\Hr\Qualification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobPortalController extends Controller
{
    /**
     * Display list of published job vacancies
     */
    public function index()
    {
        $jobs = VacancyRequisition::where('published_to_portal', true)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereNull('posting_end_date')
                    ->orWhere('posting_end_date', '>=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('posting_start_date')
                    ->orWhere('posting_start_date', '<=', now()->toDateString());
            })
            ->with(['position', 'department', 'company'])
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return view('public.job-portal.index', compact('jobs'));
    }

    /**
     * Display individual job details and application form
     */
    public function show(VacancyRequisition $vacancyRequisition)
    {
        // Debug: Log the requisition info
        \Log::info('Showing job details for: ' . $vacancyRequisition->id . ' status: ' . $vacancyRequisition->status . ' published: ' . $vacancyRequisition->published_to_portal);

        $job = $vacancyRequisition->load(['position', 'department', 'company']);

        // Check if job is still within posting dates (for informational display only, don't abort yet for debugging)
        $isActive = true;
        
        if ($job->opening_date) {
            $openingDate = \Carbon\Carbon::parse($job->opening_date)->startOfDay();
            if ($openingDate->isFuture()) {
                $isActive = false;
            }
        }
        
        if ($job->closing_date) {
            $closingDate = \Carbon\Carbon::parse($job->closing_date)->endOfDay();
            if ($closingDate->isPast()) {
                $isActive = false;
            }
        }

        // Get qualifications for the form (company-specific or global)
        $qualifications = Qualification::active()
            ->where(function ($query) use ($job) {
                $query->whereNull('company_id')
                      ->orWhere('company_id', $job->company_id);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('public.job-portal.show', compact('job', 'isActive', 'qualifications'));
    }

    /**
     * Handle job application submission
     */
    public function apply(Request $request, VacancyRequisition $vacancyRequisition)
    {
        \Log::info('Job application submission attempt', [
            'job_id' => $vacancyRequisition->id,
            'job_title' => $vacancyRequisition->job_title,
            'ip' => $request->ip()
        ]);

        // Verify the job is published and approved
        if (!$vacancyRequisition->published_to_portal || $vacancyRequisition->status !== 'approved') {
            \Log::warning('Application attempt on unpublished or unapproved job', ['job_id' => $vacancyRequisition->id]);
            abort(404, 'Job not found or not available.');
        }

        // Check visibility dates (consistent with show method)
        $today = now()->startOfDay();
        
        if ($vacancyRequisition->posting_start_date) {
            $startDate = \Carbon\Carbon::parse($vacancyRequisition->posting_start_date)->startOfDay();
            if ($startDate->isFuture()) {
                abort(404, 'Job not yet available for applications.');
            }
        }
        
        if ($vacancyRequisition->posting_end_date) {
            $endDate = \Carbon\Carbon::parse($vacancyRequisition->posting_end_date)->endOfDay();
            if ($endDate->isPast()) {
                abort(404, 'Job posting has expired.');
            }
        }

        $job = $vacancyRequisition;

        // Check if job is still accepting applications (using Application Opening/Closing dates)
        if ($job->opening_date) {
            $openingDate = \Carbon\Carbon::parse($job->opening_date)->startOfDay();
            if ($openingDate->isFuture()) {
                return back()->with('error', 'Applications for this position have not yet opened.')->withInput();
            }
        }
        
        if ($job->closing_date) {
            $closingDate = \Carbon\Carbon::parse($job->closing_date)->endOfDay();
            if ($closingDate->isPast()) {
                return back()->with('error', 'This position is no longer accepting applications.')->withInput();
            }
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string|max:500',
            'qualification' => 'nullable|string|max:200', // Keep for backward compatibility
            'qualifications' => 'nullable|array',
            'qualifications.*.qualification_id' => 'required|exists:hr_qualifications,id',
            'qualifications.*.documents' => 'nullable|array',
            'qualifications.*.documents.*' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'years_of_experience' => 'nullable|integer|min:0',
            'cover_letter' => 'nullable|string|max:5000',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB max
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB max
        ]);

        DB::beginTransaction();
        try {
            // Generate application number
            $count = Applicant::where('company_id', $job->company_id)
                ->whereYear('created_at', now()->year)
                ->count() + 1;
            $applicationNumber = 'APP-' . now()->year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);

            // Handle file uploads
            $resumePath = null;
            $cvPath = null;

            if ($request->hasFile('resume')) {
                $resumePath = $request->file('resume')->store('applicants/resumes', 'public');
            }

            if ($request->hasFile('cv')) {
                $cvPath = $request->file('cv')->store('applicants/cvs', 'public');
            }

            // Process qualifications and their documents
            $qualificationsData = [];
            $qualificationDocumentsData = [];

            if ($request->has('qualifications') && is_array($request->qualifications)) {
                foreach ($request->qualifications as $rowId => $qualData) {
                    if (isset($qualData['qualification_id'])) {
                        $qualification = Qualification::find($qualData['qualification_id']);
                        if ($qualification) {
                            $qualificationsData[] = [
                                'qualification_id' => $qualification->id,
                                'qualification_name' => $qualification->name,
                                'qualification_level' => $qualification->level,
                            ];

                            // Handle documents for this qualification
                            if (isset($qualData['documents']) && is_array($qualData['documents'])) {
                                foreach ($qualData['documents'] as $docId => $file) {
                                    if ($request->hasFile("qualifications.{$rowId}.documents.{$docId}")) {
                                        $uploadedFile = $request->file("qualifications.{$rowId}.documents.{$docId}");
                                        $documentPath = $uploadedFile->store("applicants/qualifications/{$qualification->id}", 'public');
                                        
                                        $qualificationDocumentsData[] = [
                                            'qualification_id' => $qualification->id,
                                            'document_id' => $docId,
                                            'document_path' => $documentPath,
                                            'document_name' => $uploadedFile->getClientOriginalName(),
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Create applicant record
            $applicant = Applicant::create([
                'company_id' => $job->company_id,
                'vacancy_requisition_id' => $job->id,
                'application_number' => $applicationNumber,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'] ?? null,
                'address' => $validated['address'] ?? null,
                'qualification' => $validated['qualification'] ?? null, // Keep for backward compatibility
                'qualifications' => !empty($qualificationsData) ? $qualificationsData : null,
                'qualification_documents' => !empty($qualificationDocumentsData) ? $qualificationDocumentsData : null,
                'years_of_experience' => $validated['years_of_experience'] ?? 0,
                'cover_letter' => $validated['cover_letter'] ?? null,
                'resume_path' => $resumePath,
                'cv_path' => $cvPath,
                'status' => Applicant::STATUS_APPLIED,
                'submission_source' => 'portal',
            ]);

            // Perform eligibility check
            $eligibilityService = app(\App\Services\Hr\EligibilityService::class);
            $eligibilityResult = $eligibilityService->checkApplicantEligibility($applicant, $vacancyRequisition);

            if (!$eligibilityResult['eligible']) {
                // Mandatory rule failed - Reject the application
                $applicant->update([
                    'status' => Applicant::STATUS_REJECTED,
                    'total_eligibility_score' => $eligibilityResult['total_score'] ?? 0
                ]);
                
                DB::commit();
                
                \Log::info('Applicant rejected by eligibility engine', [
                    'applicant_id' => $applicant->id,
                    'vacancy_id' => $vacancyRequisition->id
                ]);

                return redirect()->route('public.job-portal.show', $vacancyRequisition->hash_id)
                    ->with('error', 'Thank you for your interest. Unfortunately, based on the information provided, your application does not meet the minimum mandatory requirements for this position.');
            }

            // Update score for qualified applicant
            $applicant->update(['total_eligibility_score' => $eligibilityResult['total_score'] ?? 0]);

            // --- NEW: Profile Normalization Stage ---
            $normalizationService = app(\App\Services\Hr\NormalizationService::class);
            $normalizationService->normalizeProfile($applicant);

            DB::commit();

            return redirect()->route('public.job-portal.show', $job->hash_id)
                ->with('success', 'Your application has been submitted successfully! Application Number: ' . $applicationNumber);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to submit application: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Get required documents for a qualification (API endpoint)
     */
    public function getQualificationDocuments($qualificationId)
    {
        try {
            $qualification = Qualification::with('requiredDocumentsList')->findOrFail($qualificationId);
            
            return response()->json([
                'success' => true,
                'documents' => $qualification->requiredDocumentsList->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'document_name' => $doc->document_name,
                        'document_type' => $doc->document_type,
                        'is_required' => $doc->is_required,
                        'description' => $doc->description,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Qualification not found',
                'documents' => []
            ], 404);
        }
    }
}
