import React, { useMemo } from 'react';

const crumbsDir = () => {
    if (typeof document === 'undefined') return 'ltr';
    return document.documentElement.getAttribute('dir') === 'rtl' ? 'rtl' : 'ltr';
};

const Breadcrumbs = ({ items = [] }) => {
    if (!items || items.length === 0) {
        return null;
    }

    const dir = useMemo(() => crumbsDir(), []);

    return (
        <ul
            className="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1 justify-content-start text-start"
            dir={dir}
            style={{ textTransform: 'capitalize' }}
        >
            {items.map((item, index) => (
                <React.Fragment key={index}>
                    <li className="breadcrumb-item text-muted">
                        {item.url ? (
                            <a 
                                href={item.url} 
                                className="text-muted text-hover-primary"
                            >
                                {item.label}
                            </a>
                        ) : (
                            <span className={item.active ? 'text-dark' : 'text-muted'}>
                                {item.label}
                            </span>
                        )}
                    </li>
                    
                    {/* Separator */}
                    {index < items.length - 1 && (
                        <li className="breadcrumb-item">
                            <span className="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                    )}
                </React.Fragment>
            ))}
        </ul>
    );
};

export default Breadcrumbs;

