import React, { useState, useEffect } from 'react';

export default function RichTextEditor({ value = '', onChange, placeholder = 'Type your text here...' }) {
    const [content, setContent] = useState(value);

    useEffect(() => {
        setContent(value);
    }, [value]);

    const handleChange = (e) => {
        const newValue = e.target.value;
        setContent(newValue);
        onChange && onChange(newValue);
    };

    return (
        <div className="rich-text-editor">
            <textarea
                className="form-control"
                rows="6"
                value={content}
                onChange={handleChange}
                placeholder={placeholder}
                style={{ minHeight: '200px' }}
            />
            <div className="form-text text-muted mt-2">
                Product description supports basic text formatting
            </div>
        </div>
    );
}

