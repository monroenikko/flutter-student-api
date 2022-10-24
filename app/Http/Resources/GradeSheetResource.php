<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GradeSheetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'section' => $this['classDetail']['section']['section'],
            'grade_level' => $this['classDetail']['grade_level'],
            'grades' => $this['studentEnrolledSubjects']->map( function ($item) use ($request) {
                return [
                    'subject' => $item['classSubjectDetails']['subjectDetails']['subject'],
                    'subject_code' => $item['classSubjectDetails']['subjectDetails']['subject_code'],
                    'fir_g' => (int) $item['fir_g'],
                    'sec_g' => (int) $item['sec_g'],
                    'thi_g' => (int) $item['thi_g'],
                    'fou_g' => (int) $item['fou_g'],
                    'final_g' => $this->finalGrade($item, $request),
                    'faculty' => $item['classSubjectDetails']['assignFaculty']['full_name'],
                    'order' => $item['classSubjectDetails']['class_subject_order'],
                ];
            })
            ->sortBy('order')
            ->toArray()
        ];
   }

   private function finalGrade($item, $data)
   {
        // dd($data['sem']);
        if($this['classDetail']['grade_level'] >= 11)
        {
            switch ($data['sem']) {
                case 1:

                    $sum = 0;
                    $first = $item['fir_g'] > 0 ? $item['fir_g'] : 0;
                    $second = $item['sec_g'] > 0 ? $item['sec_g'] : 0;

                    $sum += $item['fir_g'] > 0 ? $item['fir_g'] : 0;
                    $sum += $item['sec_g'] > 0 ? $item['sec_g'] : 0;

                    $divisor = 0;
                    $divisor += $first > 0 ? 1 : 0;
                    $divisor += $second > 0 ? 1 : 0;

                    $final = 0;
                    if ($divisor != 0)
                    {
                        $final = $sum / $divisor;
                    }

                    break;

                case 2:

                    $sum = 0;
                    $first = $item['thi_g'] > 0 ? $item['thi_g'] : 0;
                    $second = $item['fou_g'] > 0 ? $item['fou_g'] : 0;

                    $sum += $item['thi_g'] > 0 ? $item['thi_g'] : 0;
                    $sum += $item['fou_g'] > 0 ? $item['fou_g'] : 0;

                    $divisor = 0;
                    $divisor += $first > 0 ? 1 : 0;
                    $divisor += $second > 0 ? 1 : 0;

                    $final = 0;
                    if ($divisor != 0)
                    {
                        $final = $sum / $divisor;
                    }

                    break;
            }
        }

        if($this['classDetail']['grade_level'] <= 10){

            $sum = 0;
            $first = $item->fir_g > 0 ? $item->fir_g : 0;
            $second = $item->sec_g > 0 ? $item->sec_g : 0;
            $third = $item->thi_g > 0 ? $item->thi_g : 0;
            $fourth = $item->fou_g > 0 ? $item->fou_g : 0;

            $sum += $item->fir_g > 0 ? $item->fir_g : 0;
            $sum += $item->sec_g > 0 ? $item->sec_g : 0;
            $sum += $item->thi_g > 0 ? $item->thi_g : 0;
            $sum += $item->fou_g > 0 ? $item->fou_g : 0;

            $divisor = 0;
            $divisor += $first > 0 ? 1 : 0;
            $divisor += $second > 0 ? 1 : 0;
            $divisor += $third > 0 ? 1 : 0;
            $divisor += $fourth > 0 ? 1 : 0;

            $final = 0;
            if ($divisor != 0)
            {
                $final = $sum / $divisor;
            }

        }

        return $final;
   }
}
