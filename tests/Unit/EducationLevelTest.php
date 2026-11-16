<?php

use App\Enums\EducationLevel;

it('labels levels the way Youthline staff write them', function () {
    expect(EducationLevel::Form3->label())->toBe('Form 3')
        ->and(EducationLevel::Year1->label())->toBe('Year 1')
        ->and(EducationLevel::Form4->stage())->toBe('secondary')
        ->and(EducationLevel::Year5->stage())->toBe('tertiary');
});

it('promotes within a stage but never automatically from Form 4 to university', function () {
    expect(EducationLevel::Form1->next())->toBe(EducationLevel::Form2)
        ->and(EducationLevel::Year4->next())->toBe(EducationLevel::Year5)
        ->and(EducationLevel::Form4->next())->toBeNull()
        ->and(EducationLevel::Year5->next())->toBeNull();
});

it('lists all nine levels in order', function () {
    expect(array_column(EducationLevel::options(), 'value'))->toBe([
        'form_1', 'form_2', 'form_3', 'form_4', 'year_1', 'year_2', 'year_3', 'year_4', 'year_5',
    ]);
});
