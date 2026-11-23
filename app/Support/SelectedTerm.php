<?php

namespace App\Support;

use App\Models\AcademicTerm;
use Illuminate\Http\Request;

/**
 * The term whose class sessions the signed-in user is looking at: the one
 * they picked, or the current one.
 */
class SelectedTerm
{
    private const SESSION_KEY = 'academic_term_id';

    public static function get(): AcademicTerm
    {
        $id = session(self::SESSION_KEY);

        return ($id ? AcademicTerm::find($id) : null) ?? AcademicTerm::current();
    }

    public static function set(AcademicTerm $term): void
    {
        // Following the current term means moving on with it.
        $term->is_current
            ? session()->forget(self::SESSION_KEY)
            : session([self::SESSION_KEY => $term->id]);
    }

    /**
     * The term a form was opened for, which stays the same if another tab
     * switches to a different one before it is saved.
     */
    public static function fromForm(Request $request): AcademicTerm
    {
        return AcademicTerm::find($request->integer('academic_term_id')) ?? self::get();
    }
}
