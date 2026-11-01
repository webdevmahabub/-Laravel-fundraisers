<?php

namespace Database\Factories;

use App\Models\OrganizerProfile;
use App\Models\VerificationDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationDocument>
 */
class VerificationDocumentFactory extends Factory
{
    protected $model = VerificationDocument::class;

    public function definition(): array
    {
        return [
            'organizer_profile_id' => OrganizerProfile::factory()->pending(),
            'type' => fake()->randomElement([
                'national_id', 'passport', 'drivers_license', 'other',
            ]),
            'disk' => 'local',
            'path' => 'verification-documents/test/fake.pdf',
            'original_filename' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 102_400,
            'status' => 'pending',
            'uploaded_at' => now(),
        ];
    }
}
