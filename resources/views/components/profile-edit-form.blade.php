<form action="{{route('profiles.update', ["type" => $type, "profile" => $user->id])}}"
      method="post"
      enctype="multipart/form-data">
    @csrf
    @method("put")
    <div class="row">
        <div class="row col-md-9">
            <div class="card p-5 ">
                <div class="card-header">
                    <div class="card-title">
                        <h3 class="card-label">{{___('translation.profile_information')}}</h3>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="col-md-12">
                        <div class="card p-4">
                            <div class="row">
                                <x:text-input class="col-md-6" name='name' filedname="name"
                                              value="{{$user->name}}"/>
                                <x:text-input class="col-md-6" name='email' filedname="email"
                                              value="{{$user->email}}"/>
                                <x:text-input class="col-md-6" name='phone' filedname="phone"
                                              value="{{$user->phone}}"/>
                                <x:text-input class="col-md-6 d-none" name='type' filedname="type"
                                              value="{{$type}}"/>
                                <x:select2-input class="col-md-6" name="nationality"
                                                 filed-name="nationality"
                                                 url="{{route('countries.select')}}"
                                                 :name-value="$user->Nationality->name ?? null "
                                                 :value="$user->nationality "/>
                                <x:select2-input class="col-md-6" name="country" filed-name="country_id"
                                                 url="{{route('countries.select')}}"
                                                 :name-value="$user->Country->name ?? null "
                                                 :value="$user->country_id "/>
                                <x:select2-input class="col-md-6" name="city" filed-name="city_id"
                                                 url="{{route('city.select')}}"
                                                 :name-value="$user->City->name ?? null "
                                                 :value="$user->city_id "/>
                                @if($type == "expert")
                                    <x:select-options class="col-md-6" name="gender" filed-name="gender"
                                                      :value="$user->gender" :options="['male', 'female']"/>

                                    <div class="mb-10 col-md-6">
                                        <label for="exampleFormControlInput1" class="required form-label">brith
                                            date</label>
                                        <input type="date" class="form-control form-control-solid"
                                               name="brith_date" value="{{$user->brith_date}}"/>
                                    </div>
                                @endif
                            </div>
                        </div><!-- end of tile -->
                    </div><!-- end of col -->
                </div>
            </div>

            <div class="card p-5 mt-4">
                <div class="card-header">
                    <div class="card-title">
                        <h3 class="card-label">{{$type == "expert" ? ___('translation.expert_information') : ___('translation.business_information')}}</h3>
                    </div>
                </div>
                <div class="card-body   p-3">
                    <div class="col-md-12">
                        <div class="card p-4">
                            <div class="row">
                                @if($type == "company")
                                    <x:text-input class="col-md-6" name='business_name'
                                                  filedname="business_name[en]"
                                                  value="{{$user->Profile->getTranslation('business_name', 'en')}}"/>

                                    <x:text-input class="col-md-6" name='business_name_in_arabic'
                                                  filedname="business_name[ar]"
                                                  value="{{$user->Profile->getTranslation('business_name', 'ar')}}"/>
                                    <x:text-input class="col-md-6" name='business_license'
                                                  filedname="business_license"
                                                  value="{{$user->Profile->business_license}}"/>
                                    <x:select2-input class="col-md-6" name="business_category"
                                                     filed-name="category_id"
                                                     url="{{route('categories.ajax', ['type' => 'company'])}}"
                                                     :name-value="$user->Profile->Category->name ?? null "
                                                     :value="$user->Profile->category_id "/>
                                @else
                                    <x:text-input class="col-md-6" name='business_license'
                                                  filedname="business_license"
                                                  value="{{$user->Profile->business_license}}"/>

                                    <x:select2-input class="col-md-6" name="Person Category"
                                                     filed-name="profession"
                                                     :url="route('categories.ajax', ['type' => 'company'])"
                                                     :name-value="$user->Profile->Profession->name ?? null "
                                                     :value="$user->Profile->profession"/>
                                @endif

                                <x:date-input class="col-md-6" name='experience_since'
                                              filedname="experience_since"
                                              value="{{$user->Profile->experience_since}}"/>

                                <x:date-input class="col-md-6" name='expired_date'
                                              filedname="expired_date"
                                              value="{{$user->Profile->expired_date}}"/>
                                @if($type == 'company')
                                    <x:select2-multiple class="col-md-6" name="country_of_operation"
                                                        filed-name="country_of_operation[]"
                                                        url="{{route('countries.select')}}"
                                                        :selected-url="route('countries.ids')"
                                                        :value="implode(',', $countries)"
                                    />

                                    <x:select2-multiple class="col-md-6" name="activity_type"
                                                        filed-name="activity_area[]"
                                                        url="{{route('subcategories.select', ['type' => 'company'])}}"
                                                        :value="implode(',', $categories)"
                                                        :selected-url="route('subcategories.ids')"
                                    />

                                    <x:select2-input class="col-md-6" name="classification"
                                                     filed-name="label_id"
                                                     url="{{route('labels.select')}}"
                                                     :name-value="$user->Profile->Label->name ?? null "
                                                     :value="$user->Profile->label_id"
                                    />
                                @endif
                                <x:text-area class="col-md-6"
                                             name="{{$type == 'company' ? 'about_business': 'bio' }}"
                                             filed-name="about_business"
                                             value="{{$user->Profile->about_business}}"/>


                            </div>
                        </div><!-- end of tile -->
                    </div><!-- end of col -->
                </div>
            </div>
            @if($type == 'company')
                <div class="card p-5 mt-4">
                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="card-label">{{$type == "expert" ? ___('translation.expert_contact') : ___('translation.business_contact')}}</h3>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="col-md-12">
                            <div class="card p-4">
                                <div class="row">
                                    <x:text-input class="col-md-6" name='map_location' filedname="location"
                                                  value="{{$user->Profile->location}}"/>
                                    <x:text-area class="col-md-6" name="address"
                                                 value="{{$user->Profile->address}}"/>

                                </div>
                            </div><!-- end of tile -->
                        </div><!-- end of col -->
                    </div>
                </div>
            @endif
        </div><!-- en of row -->
        <div class="col-md-3">
            <div class="card-body p-3 ">
                <div class="col-md-12">
                    <div class="card p-4">
                        <x:image-picker value="{{$user->getProfileImageApi()}}" class="col-md-12"
                                        name="{{$type == 'company' ? 'company_logo_image' : 'expert_image' }}"
                                        filed-name='image2'
                                        real-filed-id="profile_image"/>
                        <input type="file" name="profile_image" id="profile_image" class="d-none">
                    </div>
                </div>
                <div class="col-md-12 mt-2">
                    <div class="card p-4">
                        <x:image-picker value="{{$banner_image ? asset($banner_image): null}}"
                                        class="col-md-12"
                                        name="banner"
                                        filed-name='image2'
                                        real-filed-id="banner_image"/>
                        <input type="file" name="banner_image" id="banner_image" class="d-none">
                    </div>
                </div>
                <div class="col-md-12 mt-2">
                    <div class="card p-4">
                        <x:image-picker class="col-md-12"
                                        :name='$type == "company"? "trade_proof_doc" : "expert_licences"'
                                        value='{{ $type == "company" ? ($trade_proof ? asset($trade_proof): null):  ($expert_licences ? asset($expert_licences): null) }}'
                                        filed-name='image2' real-filed-id="trade_proof"/>
                        <input type="file" name='{{$type == "company"? "trade_proof" : "expert_licences"}}'
                               id="trade_proof" class="d-none">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-10">
        @if ($errors->any())
            <div class="alert alert-danger mt-3">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <button class="btn-primary btn">
            {{__('translation.update')}}
        </button>
        <a href="#" onclick="window.history.back()" class="btn btn-light-danger">
            {{__('translation.cancel')}}
        </a>
    </div>
</form>
