<!DOCTYPE html>
<!--
Author: Corenet Tech
Product Name: Corenet Tech POS System
Product Version: 2.0.0
Website: https://corenettech.com
Contact: info@corenettech.com
Company: Corenet Tech
License: Proprietary software owned by Corenet Tech
-->
<html lang="en">
<!--begin::Head-->

<head>
    <base href="../" />
    <title>@yield('main-head', 'Corenet Tech Admin Dashboard')</title>
    <meta charset="utf-8" />
    <meta name="description" content="@yield('meta_description', 'Corenet Tech Admin Dashboard - Point of Sale Management System')" />
    <meta name="keywords" content="@yield('meta_keywords', 'corenet tech, pos, admin, dashboard, point of sale')" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="@yield('og_title', 'Corenet Tech Admin Dashboard')" />
    <meta property="og:url" content="@yield('og_url', url('/'))" />
    <meta property="og:site_name" content="@yield('og_site_name', 'Corenet Tech')" />
    <link rel="canonical" href="@yield('canonical_url', url()->current())" />
    <link rel="shortcut icon" href="{{ asset('logo_dark.png') }}" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Vendor Stylesheets(used for this page only)-->
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/custom/vis-timeline/vis-timeline.bundle.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Vendor Stylesheets-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->
    
    <link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet"/>

    <!-- FilePond JavaScript -->
    <script src="https://unpkg.com/filepond/dist/filepond.js"></script>

    <!-- FilePond Plugin (for images) -->
    <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css"
          rel="stylesheet"/>

    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>
    @stack('styles')
</head>
<!--end::Head-->
<!--begin::Body-->

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>
    <!--end::Theme mode setup on page load-->
    <!--begin::App-->
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <!--begin::Page-->
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            @include('layouts.admin.partials.header')
           
            <!--end::Header-->
            <!--begin::Wrapper-->
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                @include('layouts.admin.partials.sidebar')
                <!--begin::Main-->
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <!--begin::Content wrapper-->
                    <div class="d-flex flex-column flex-column-fluid">
                        @include('layouts.admin.partials.toolbar')
                        <!--begin::Content-->
                        <div id="kt_app_content" class="app-content flex-column-fluid">
                            <!--begin::Content container-->
                            <div id="kt_app_content_container" class="app-container container-xxl">
                                @yield('content')
                            </div>
                            <!--end::Content container-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Content wrapper-->
                    @include('layouts.admin.partials.footer')
                </div>
                <!--end:::Main-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Page-->
    </div>
    <!--end::App-->
    @include('layouts.admin.partials.drawers')
    <!--begin::Scrolltop-->
    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-duotone ki-arrow-up">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </div>
    <!--end::Scrolltop-->
    @include('layouts.admin.partials.modals')
    <!--begin::Javascript-->
    <script>
        var hostUrl = "{{ asset('assets/') }}";
    </script>
    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <!--end::Global Javascript Bundle-->
    <!--begin::Vendors Javascript(used for this page only)-->
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/vis-timeline/vis-timeline.bundle.js') }}"></script>
    <!--end::Vendors Javascript-->
    <!--begin::Custom Javascript(used for this page only)-->
    <script src="{{ asset('assets/js/widgets.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/custom/widgets.js') }}"></script>
    <script src="{{ asset('assets/js/custom/apps/chat/chat.js') }}"></script>
    <script src="{{ asset('assets/js/custom/utilities/modals/upgrade-plan.js') }}"></script>
    <script src="{{ asset('assets/js/custom/utilities/modals/users-search.js') }}"></script>
    <!--end::Custom Javascript-->
    <!--end::Javascript-->
    <script src="{{ asset('datatable/select2.min.js') }}"></script>
<script>

    function linksChange(element) {
        // Navigate to the parent element, then find the next sibling
        const nextSibling = element.parentElement.nextElementSibling;

        // Find the input with class 'link_type' inside the next sibling and set its value
        const linkTypeInput = nextSibling.querySelector('.link_type');
        if (linkTypeInput) {
            const url = element.value.toLowerCase();

            // Check the URL for specific platforms
            if (url.includes("facebook.com")) {
                linkTypeInput.value = "Facebook";
            } else if (url.includes("x.com") || url.includes("twitter.com")) {
                linkTypeInput.value = "Twitter";
            } else if (url.includes("instagram.com")) {
                linkTypeInput.value = "Instagram";
            } else if (url.includes("linkedin.com")) {
                linkTypeInput.value = "LinkedIn";
            } else {
                linkTypeInput.value = "Website";
            }
        }
    }


    // 

    function DeleteApp(val) {
        Swal.fire({
            title: "@lang('translation.Are_you_sure')",
            text: "@lang('translation.You_will_not_be_able_to_back_down_from_this')",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: "@lang('translation.YES_Delete_it')",
            cancelButtonText: "@lang('translation.cancel')"
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire(
                    " @lang('translation.Deleted')",
                    " @lang('translation.success')",
                    " @lang('translation.Your_file_has_been_deleted.')"
                );
                document.getElementById(val).submit();
            }
        });
    }


    function confirmButton(event) {
        let element = event.target;

        // Get the 'data-message' and 'data-route' attributes
        let message = element.getAttribute("data-message");
        let route = element.getAttribute("data-route");

        // Show confirmation dialog with SweetAlert
        Swal.fire({
            title: message ?? "Are You Sure?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes!"
        }).then((result) => {
            if (result.isConfirmed) {
                // Make the GET request to the specified route
                fetch(route)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status) {
                            console.log(data)
                            // If status is true (success), show success Swal
                            Swal.fire({
                                title: "Success!",
                                text: data.data || "Operation completed successfully.",
                                icon: "success"
                            });

                            setTimeout(() => location.reload(), 1500)
                        } else {
                            // If status is false (error), show error Swal with the server message
                            Swal.fire({
                                title: "Error!",
                                text: data.message || "Something went wrong. Please try again.",
                                icon: "error"
                            });
                        }
                    })
                    .catch(error => {
                        // In case of network error, show error Swal
                        Swal.fire({
                            title: "Error!",
                            text: "Network error. Please try again.",
                            icon: "error"
                        });
                    });
            }
        });
    }

    function base64ToFile(base64String, defaultFileName = 'file') {
        if (!base64String) return null;
        if (!base64String.includes(',')) {
            console.error('Invalid base64 string format');
            return null;
        }

        // Extract MIME type and Base64 data
        const [metadata, base64Data] = base64String.split(',');
        const mimeMatch = metadata.match(/^data:(.*?);base64$/);

        if (!mimeMatch) {
            console.error('Invalid base64 metadata');
            return null;
        }

        const mimeType = mimeMatch[1]; // Extracted MIME type
        const byteCharacters = atob(base64Data);

        // Convert binary string to Uint8Array efficiently
        const byteArray = Uint8Array.from(byteCharacters, char => char.charCodeAt(0));

        // Create a File object
        return new File([byteArray], `${defaultFileName}.${mimeType.split('/')[1] || 'bin'}`, {type: mimeType});
    }

    function getFilesFromLocalStorage(key) {
        const fileArray = JSON.parse(localStorage.getItem(key)) || []; // Retrieve and parse stored data
        return fileArray.map(base64String => base64ToFile(base64String)).filter(file => file); // Convert and filter null values
    }

    if ($("#error_message").length) {
        let base64String = localStorage.getItem("file_pond_" + imageId);
        fileFormStorage = base64ToFile(base64String, 'cropped_image.png');
    } else {
        Object.keys(localStorage).filter(key => key.startsWith("file_pond_")).forEach(key => localStorage.removeItem(key));
        console.log(Object.keys(localStorage).filter(key => key.startsWith("file_pond_")));
    }


    function debounce(callback, delay) {
        let timeout;
        return function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => callback.apply(this, arguments), delay);
        };
    }

    // initializeConfirmationModels();
</script>
<script>
    let _multi_dataTransfer = new DataTransfer();
    let _multi_originalFiles = [];
    let _multi_currentIndex = 0;
    let _multi_cropper = null;
    let _multi_fileInput = null;
    let _multi_filepond = null;

    function handleMultipleImageCrop(files, fileInput, filepond) {
        _multi_dataTransfer = new DataTransfer(); // reset
        _multi_originalFiles = Array.from(files);
        _multi_currentIndex = 0;
        _multi_fileInput = fileInput;
        _multi_filepond = filepond;

        filepond.removeFiles({instant: true}).then(() => console.log('cleared'));
        showMultiCropModal(_multi_originalFiles[_multi_currentIndex], filepond);
    }

    function removeImagesFromInputManly() {
        window.filepond.removeFiles();
        Array.from(document.getElementById('project_images').files).forEach(file => {
            window.filepond.addFile(file);
        });
    }

    function showMultiCropModal(file, filepond) {
        window.filepond = filepond;
        if (_multi_currentIndex === 0) {
            filepond.removeFiles();
            console.log('delete', filepond, '--------- index 0 ------');
        }

        const image = document.getElementById('_multi_cropImage');
        const reader = new FileReader();

        reader.onload = function (e) {
            image.src = e.target.result;

            const modalElement = document.getElementById('_multi_crop_image');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            // Listen once for modal fully shown event
            modalElement.addEventListener('shown.bs.modal', function onModalShown() {
                modalElement.removeEventListener('shown.bs.modal', onModalShown);

                if (_multi_cropper) {
                    _multi_cropper.destroy();
                }

                _multi_cropper = new Cropper(image, {
                    aspectRatio: 1, // Change as needed
                    viewMode: 1
                });

                // Free crop
                document.getElementById('_multi_freeCropBtn').onclick = function () {
                    _multi_cropper.setAspectRatio(NaN);
                };

                // Crop and continue
                document.getElementById('_multi_cropButton').onclick = function () {
                    _multi_cropper.getCroppedCanvas().toBlob(blob => {
                        const croppedFile = new File([blob], file.name, {type: file.type});

                        _multi_dataTransfer.items.add(croppedFile);
                        filepond.addFile(croppedFile);
                        _multi_cropper.destroy();
                        _multi_cropper = null;

                        modal.hide();

                        _multi_currentIndex++;
                        if (_multi_currentIndex < _multi_originalFiles.length) {
                            showMultiCropModal(_multi_originalFiles[_multi_currentIndex], filepond);
                        } else {
                            _multi_fileInput.files = _multi_dataTransfer.files;
                            removeImagesFromInputManly();

                            // Optional: Store file metadata in localStorage
                            const meta = Array.from(_multi_dataTransfer.files).map(f => ({
                                name: f.name,
                                type: f.type,
                                size: f.size
                            }));
                            localStorage.setItem("file_pond_" + _multi_fileInput.id, JSON.stringify(meta));

                            console.log("All cropped files added to input.");
                        }
                    }, file.type);
                };
            });
        };

        reader.readAsDataURL(file);
    }
</script>


<script>
    function initializeFilePondWithCropping() {
        // Register the plugin
        FilePond.registerPlugin(FilePondPluginImagePreview);

        const inputElements = document.querySelectorAll('.filepond');
        if(!inputElements.length) return false;
        let cropper;
        let currentInputElement;
        let isCropping = false;

        inputElements.forEach(inputElement => {
            let imagePath = inputElement.getAttribute('data-value-url');
            const imageId = inputElement.getAttribute('id');

            let url = decodeURIComponent(imagePath || '').replace(/\\/g, "/");

            let fileFormStorage;
            const pond = FilePond.create(inputElement);

            if (document.getElementById("error_message")) {
                let base64String = localStorage.getItem("file_pond_" + imageId);
                fileFormStorage = base64ToFile(base64String, 'cropped_image.png');
            } else {
                Object.keys(localStorage).filter(key => key.startsWith("file_pond_")).forEach(key => localStorage.removeItem(key));
            }

            pond.setOptions({
                acceptedFileTypes: ['image/gif', 'image/jpeg', 'image/png'],
                files: imagePath ? [{source: url}] : [],
            });

            if (fileFormStorage) {
                pond.addFile(fileFormStorage);
                const realInputId2 = inputElement.getAttribute('data-real-file-id');
                const realInputElement2 = document.getElementById(realInputId2);
                const dataTransferOLD = new DataTransfer();
                dataTransferOLD.items.add(fileFormStorage);
                realInputElement2.files = dataTransferOLD.files;
            }

            pond.on('addfile', (error, file) => {
                if (error) {
                    console.error('FilePond error:', error);
                    return;
                }

                const CorperImages = ["company_logo_image", "company_banner", "_cover_image", "project_image", 'banner', 'expert_image'];
                if ((file.source && typeof file.source === 'string') || fileFormStorage) {
                    console.log("Image added from URL, no cropping needed.");
                    return;
                }

                if (CorperImages.includes(imageId)) {
                    if (isCropping) {
                        isCropping = false;
                        return;
                    }

                    currentInputElement = inputElement;

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const cropImage = document.getElementById('cropImage');
                        cropImage.src = e.target.result;
                        cropImage.style.display = 'block';

                        if (cropper) cropper.destroy();

                        const covers = ["company_banner", "_cover_image", "project_image", 'banner'];
                        const isWideBanner = covers.includes(imageId);

// Match the image’s wide layout better (like a banner)

                        cropper = new Cropper(cropImage, {
                            aspectRatio: isWideBanner ? 3 : 1,
                            viewMode: 1,
                            guides: true,
                            dragMode: 'move',
                            scalable: true,
                            zoomable: true,
                            responsive: true,
                            minContainerWidth: 700,
                            minContainerHeight: 500,
                        });

                        // Later: Enable free drag (manual cropping)
                        document.getElementById("freeCropBtn").addEventListener("click", () => {
                            cropper.setAspectRatio(NaN); // Removes the fixed aspect ratio
                        });

                        const modal = new bootstrap.Modal(document.getElementById('crop_image'), {
                            backdrop: 'static',
                            keyboard: false
                        });
                        modal.show();
                    };

                    reader.readAsDataURL(file.file);
                } else {
                    const id = inputElement.getAttribute('data-real-file-id');
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file.file);
                    document.getElementById(id).files = dataTransfer.files;

                    const selectedFile = dataTransfer.files[0];
                    if (selectedFile) {
                        const reader = new FileReader();
                        reader.onloadend = function () {
                            const base64String = reader.result;
                            const storageKey = "file_pond_" + inputElement.getAttribute("id");
                            localStorage.setItem(storageKey, base64String);
                            console.log("Base64 stored in localStorage:", storageKey);
                        };
                        reader.readAsDataURL(selectedFile);
                    }
                }
            });
        });

        // ✅ Crop Button Handler
        document.getElementById('cropButton').addEventListener('click', function () {
            if (cropper && currentInputElement) {
                const croppedCanvas = cropper.getCroppedCanvas({
                    maxWidth: 1920,
                    maxHeight: 1080
                });

                croppedCanvas.toBlob(blob => {
                    const file = new File([blob], "cropped_image.png", {type: "image/png"});

                    const reader = new FileReader();
                    reader.onloadend = function () {
                        const realInputId = currentInputElement.getAttribute('data-real-file-id');
                        const realInputElement = document.getElementById(realInputId);

                        const base64String = reader.result;
                        localStorage.setItem("file_pond_" + currentInputElement.getAttribute("id"), base64String);

                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        realInputElement.files = dataTransfer.files;

                        isCropping = true;

                        const pondInstance = FilePond.find(currentInputElement);
                        if (pondInstance) {
                            pondInstance.removeFiles();
                            pondInstance.addFile(file);
                        }

                        console.log("Cropped image set to real file input:", realInputElement.files);

                        bootstrap.Modal.getInstance(document.getElementById('crop_image')).hide();
                    };

                    reader.readAsDataURL(file);
                }, "image/png", 1);
            }
        });

        // ✅ Cancel Button Handler
        document.getElementById('cancelCropButton').addEventListener('click', function () {
            const cropModal = bootstrap.Modal.getInstance(document.getElementById('crop_image'));
            if (cropModal) cropModal.hide();

            if (currentInputElement) {
                const realInputId = currentInputElement.getAttribute('data-real-file-id');
                const realInputElement = document.getElementById(realInputId);

                if (realInputElement) {
                    realInputElement.value = '';
                }

                const pondInstance = FilePond.find(currentInputElement);
                if (pondInstance) {
                    pondInstance.removeFiles();
                }

                console.log("Cropping canceled. File input and preview cleared.");
            }
        });
    }

    initializeFilePondWithCropping();
</script>
<script>
    // Register the plugin
    FilePond.registerPlugin(FilePondPluginImagePreview);

    // Get the FilePond input elements
    const fileInputs = document.querySelectorAll('.fileponds');

    console.log(fileInputs);
    fileInputs.forEach(fileInput => {
        const imagePaths = fileInput.getAttribute('data-value-url');

        if (imagePaths) {

        }
// Decode the URI component
        const decodedString = decodeURIComponent(imagePaths);

// Extract the array part from the decoded string
        const arrayString = decodedString.match(/\[.*\]/)[0];

// Convert to an array of image paths by parsing JSON and removing extra quotes
        const imageArray = JSON.parse(arrayString.replace(/&quot;/g, '"'));


        const imageId = fileInput.getAttribute('id');


        const files = getFilesFromLocalStorage("file_pond_" + imageId)
        console.log(imageId, files);

        // Create a FilePond instance
        const pond = FilePond.create(fileInput);

        // Set options with existing file
        pond.setOptions({
            acceptedFileTypes: ['image/gif', 'image/jpeg', 'image/png'],
            files: imageArray,
            maxFiles: 10,
        });
        // console.log(imageId, 'image ids -----------');
        if (imageId != 'images') {
            pond.on('removefile', (error, file) => {
                if (error) {
                    console.error('Error removing file:', error);
                    return;
                }
                console.log("delete the image",
                    file.source
                )
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // Get the file ID from metadata
                // Send a DELETE request to the API
                
            });
        }

        if ($("#error_message").length > 0 && files.length > 0) {
            const id = fileInput.getAttribute('data-real-file-id');
            const dataTransfer = new DataTransfer();
            files.forEach((file) => {
                pond.addFile(file);
                dataTransfer.items.add(file);
            });
            document.getElementById(id).files = dataTransfer.files;
            console.log(document.getElementById(id).files);

        } else {
            if (!$("#error_message").length) {

            }
        }
        document.getElementById(imageId).addEventListener('change', function (e) {
            const files = e.target.files;
            const id = fileInput.getAttribute('data-real-file-id');
            const dataTransfer = new DataTransfer();
            // await pond.removeFiles(true);
            // console.log(id);
            if (id === 'project_images') {
                console.log(FilePond.find(this).removeFiles({instant: true}).then(() => console.log('cleared')));

                handleMultipleImageCrop(files, document.getElementById(id), pond);
            }

            console.log(files);
            // Loop through the files and add each to the DataTransfer object
            for (let i = 0; i < files.length; i++) {
                dataTransfer.items.add(files[i]);
            }

            // Store files in localStorage (as Base64 encoded strings or other format)
            let fileArray = [];
            for (let i = 0; i < dataTransfer.files.length; i++) {
                console.log(dataTransfer.files[i])
                const reader = new FileReader();
                reader.onload = function (event) {
                    fileArray.push(event.target.result);
                    localStorage.setItem("file_pond_" + fileInput.getAttribute("id"), JSON.stringify(fileArray));
                };
                reader.readAsDataURL(dataTransfer.files[i]);
            }

            // Set the files to the target input field
            document.getElementById(id).files = dataTransfer.files;

            console.log(document.getElementById(id).files); // Check all appended files
        });
    });
</script>

<script>
    $('.has_select_2').each(function () {
        console.log(this);
        initializeSelect2(this); // Pass the current element
    });

    function initializeSelect2(selector) {
        var value = $(selector).data("value");
        var name = $(selector).data("name");
        // console.log(name, value);
        $(selector).select2({
            ajax: {
                url: $(selector).data('url'), // Fetch the URL from data-url attribute
                type: 'get',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term // search term
                    };
                },
                processResults: function (response) {
                    return {
                        results: response
                    };
                },
                cache: true
            }
        });


        if (name && value) {
            var option = new Option(name, value, true, true); // Create a new Option
            $(selector).append(option).trigger('change');
        } else {

            $(selector).select2('open').select2('close');
        }

        $(selector).on("change", function (event) {
            localStorage.setItem("select_2_" + event.target.name, event.target.value)
        });

        // console.log( ? true : false, "-------------------------");

        if ($("#error_message").length) {
            var selectedUrl = $(selector).data('selected-url');
            const elementID = (localStorage.getItem("select_2_" + $(selector).attr("name")))
            $.ajax({
                url: selectedUrl ? selectedUrl : '{{route('categories.ids')}}',
                data: {ids: elementID?.toString()},
                success: function (data) {
                    let options = data.map(item => new Option(item.name, item.id, true, true));
                    $(selector).append(options).trigger('change');
                }
            });
        } else {
            Object.keys(localStorage).filter(key => key.startsWith("select_2")).forEach(key => localStorage.removeItem(key));
        }

    }


    $('.has_select_3').each(function () {
        initializeSelect3(this); // Pass the current element
    });

    function initializeSelect3(selector) {
        var value = $(selector).data("value");
        $(selector).select2({
            ajax: {
                url: $(selector).data('url'), // Fetch the URL from data-url attribute
                type: 'get',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term,  // search term
                    };
                },
                processResults: function (response) {
                    return {
                        results: response
                    };
                },
                cache: true
            }
        });
        var selectedUrl = $(selector).data('selected-url');
        if (value) {
            $.ajax({
                // metho
                url: selectedUrl ? selectedUrl : '{{route('categories.ids')}}',
                data: {ids: value},
                success: function (data) {
                    let options = data.map(item => new Option(item.name, item.id, true, true));
                    $(selector).append(options).trigger('change');
                }
            });

        } else {
            $(selector).select2('open').select2('close');
        }

        $(selector).on("change", function (event) {
            localStorage.setItem("select_2_" + event.target.name, $(this).val().join(","))
        });


        if ($("#error_message").length) {
            var selectedUrl = $(selector).data('selected-url');
            console.log("select_2_" + $(selector).attr("name"))
            const elementID = (localStorage.getItem("select_2_" + $(selector).attr("name")))
            console.log(elementID);
            $.ajax({
                url: selectedUrl ? selectedUrl : '{{route('categories.ids')}}',
                data: {ids: elementID},
                success: function (data) {
                    let options = data.map(item => new Option(item.name, item.id, true, true));
                    $(selector).append(options).trigger('change');
                }
            });
        } else {
            Object.keys(localStorage).filter(key => key.startsWith("select_2")).forEach(key => localStorage.removeItem(key));
        }


    }

</script>

@stack('scripts')

<!-- Vite assets for React components -->
@vite(['resources/css/app.css', 'resources/js/app.jsx'])

</body>
<!--end::Body-->

</html>
