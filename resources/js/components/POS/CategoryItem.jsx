import React from 'react';

const CategoryItem = ({ category, isActive = false, onClick }) => {
    return (
        <li className="nav-item mb-3 me-0 flex-shrink-0" role="presentation">
            <a 
                className={`nav-link nav-link-border-solid btn btn-outline btn-flex btn-active-color-primary flex-column flex-stack justify-content-center  page-bg ${isActive ? 'active' : ''}`}
                data-bs-toggle="pill" 
                href={`#kt_pos_food_content_${category.id}`}
                style={{width: '120px', height: '120px'}} 
                aria-selected={isActive}
                role="tab"
                tabIndex={isActive ? "0" : "-1"}
                onClick={onClick}
            >
                <div className="nav-icon ">
                    <img src={category.thumbnail} className="w-50px" alt={category.name} />
                </div>
                <div className="">
                    <span className="text-gray-800 fw-bold fs-4 d-block">{category.name}</span>
                    <span className="text-gray-500 fw-semibold fs-7">{category.count} {category.unit}</span>
                </div>
            </a>
        </li>
    );
};

export default CategoryItem;

