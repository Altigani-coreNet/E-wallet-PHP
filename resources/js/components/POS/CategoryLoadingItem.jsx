import React from 'react';



const CategoryLoadingItem = ({ count = 1 }) => {
    return (
        <>
            {Array.from({ length: count }).map((_, index) => (
                <li key={`loading-${index}`} className="nav-item mb-3 me-0 flex-shrink-0" role="presentation">
                    <div 
                        className="nav-link nav-link-border-solid btn btn-outline btn-flex btn-active-color-primary flex-column flex-stack justify-content-center page-bg category-loading-card"
                        style={{width: '120px', height: '120px'}} 
                    >
                        <div className="nav-icon">
                            <span className="loader3"></span>
                        </div>
                        {/* <div className="">
                            <div className="bg-light rounded" style={{height: '16px', width: '60px', marginBottom: '4px'}}></div>
                            <div className="bg-light rounded" style={{height: '12px', width: '40px'}}></div>
                        </div> */}
                    </div>
                </li>
            ))}
        </>
    );
};

export default CategoryLoadingItem;

