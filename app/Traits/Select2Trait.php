<?php

namespace App\Traits;

use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait Select2Trait
{
    /**
     * Get Select2 data with dynamic model and fields
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSelect2Data(Request $request, $modelName, array $fields = ['title']): JsonResponse
    {
        $search = $request->search;
        $type = $request->type;

        // Ensure a valid model class is passed
        if (!$modelName || !class_exists($modelName)) {
            return response()->json(['error' => 'Model not found'], 404);
        }

        // Instantiate the model dynamically
        $model = app($modelName);

        // Check if the model has the necessary traits or methods
        if (!method_exists($model, 'newQuery')) {
            throw new \Exception("model Not Exist;");
        }

        // Perform the query dynamically on the given model
        $records = $model->where('status', 1)
            ->when($search, function ($query) use ($search, $fields) {
                $query->where(function ($q) use ($search, $fields) {
                    foreach ($fields as $field) {
                        // Handle translatable fields dynamically
                        $q->orWhere($field . '->' . app()->getLocale(), 'like', '%' . $search . '%');
                    }
                });
            })
            ->when($type, function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->orderby('id', 'asc')
            ->select('id', 'name->' . app()->getLocale() . ' as text')
            ->limit(10)
            ->get();

        return response()->json($records);
    }


    public function getSelect2DataV2(Request $request, $query, array $fields = ['title'], array $filterParams = []): JsonResponse
    {
        // Ensure the query passed is valid and is an instance of Builder
        if (!$query instanceof \Illuminate\Database\Eloquent\Builder) {
            return response()->json(['error' => 'Invalid query object'], 400);
        }

        // Iterate over the filterParams array and apply them dynamically
        foreach ($filterParams as $param) {
            // Get the value from the request for the current parameter
            $value = $request->get($param);

            if ($value) {
                // Handle search separately with translatable fields
                if ($param === 'search') {
                    $query->where(function ($q) use ($value, $fields) {
                        foreach ($fields as $field) {
                            // Handle translatable fields dynamically
                            $q->orWhere($field . '->' . app()->getLocale(), 'like', '%' . $value . '%');
                        }
                    });
                } else {
                    // Apply standard where clause for the parameter and value
                    $query->where($param, $value);
                }
            }
        }

        // Fetch the filtered records
        $records = $query->where('status', 1)
            ->orderby('id', 'asc')
            ->select('id', 'name->' . app()->getLocale() . ' as text')
            ->limit(10)
            ->get();

        return response()->json($records);
    }


    public function getSelect2DataV3(Request $request, $query, array $fields = ['title'], array $filterParams = []): JsonResponse
    {
        // Ensure the query passed is valid and is an instance of Builder
        if (!$query instanceof \Illuminate\Database\Eloquent\Builder) {
            return response()->json(['error' => 'Invalid query object'], 400);
        }

        // Iterate over the filterParams array and apply them dynamically
        foreach ($filterParams as $param) {
            // Get the value from the request for the current parameter
            $value = $request->get($param);

            if ($value) {
                // Handle search separately with translatable fields
                if ($param === 'search') {
                    $query->where(function ($q) use ($value, $fields) {
                        foreach ($fields as $field) {
                            // Handle translatable fields dynamically
                            $q->orWhere($field . '->' . app()->getLocale(), 'like', '%' . $value . '%');
                        }
                    });

                } else {
                    // Apply standard where clause for the parameter and value
                    $query->where($param, $value);
                }
            }
        }

        // Fetch the filtered records
        $records = $query
            ->select('id', $fields[0] . '->' . app()->getLocale() . ' as text')
            ->limit(10)
            ->orderby('id', 'asc')
            ->get();

        return response()->json($records);
    }

    public function getSelectForProfile(Request $request): JsonResponse
    {
        // Ensure the query passed is valid and is an instance of Builder
        $query = Profile::query();

        $locale = app()->getLocale();

        $query->where(function ($q) use ($request, $locale) {
            $q->where("business_name->$locale", 'like', "%$request->search%")
                ->orWhere('business_license', 'like', "%$request->search%");
        });
        $query->OrwhereHas("User", function ($q) use ($request, $locale) {
            $q->where("name", 'like', "%$request->search%")
                ->orWhere('email', 'like', "%$request->search%")
                ->orWhere('phone', 'like', "%$request->search%");
        });
        $query->whereNotNull("user_id");
        // Fetch the filtered records
        $records = $query->with('User:id,name,last_name')
            ->select('id', "user_id", "business_name")
            ->limit(10)
            ->orderby('id', 'asc')
            ->get();

        $records->map(function ($record) {
            $record->text = $record->business_name != "" ? $record->business_name : ($record->User?->name . ' ' . $record->User?->last_name);
            return $record;
        });
        return response()->json($records);
    }


    public function getSelect2DataInNormalSearch(Request $request, $query, array $fields = ['title'], array $filterParams = []): JsonResponse
    {
        // Ensure the query passed is valid and is an instance of Builder
        if (!$query instanceof \Illuminate\Database\Eloquent\Builder) {
            return response()->json(['error' => 'Invalid query object'], 400);
        }

        // Iterate over the filterParams array and apply them dynamically
        foreach ($filterParams as $param) {
            // Get the value from the request for the current parameter
            $value = $request->get($param);

            if ($value) {
                // Handle search separately with translatable fields
                if ($param === 'search') {
                    $query->where(function ($q) use ($value, $fields) {
                        foreach ($fields as $field) {
                            // Handle translatable fields dynamically
                            $q->orWhere($field, 'like', '%' . $value . '%');
                        }
                    });
                } else {
                    // Apply standard where clause for the parameter and value
                    $query->where($param, $value);
                }
            }
        }

        // Fetch the filtered records
        $records = $query
            ->select('id', $fields[0] . ' as text')
            ->limit(10)
            ->orderby('id', 'asc')
            ->get();

        return response()->json($records);
    }


}
