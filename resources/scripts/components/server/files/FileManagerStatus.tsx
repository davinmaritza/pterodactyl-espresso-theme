import React, { useContext, useEffect } from 'react';
import { ServerContext } from '@/state/server';
import { CloudUploadIcon } from '@heroicons/react/solid';
import asDialog from '@/hoc/asDialog';
import { Dialog, DialogWrapperContext } from '@/components/elements/dialog';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import { useSignal } from '@preact/signals-react';
import { bytesToString } from '@/lib/formatters';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faFileAlt,
    faFileCode,
    faFileArchive,
    faCube,
    faCheckCircle,
    faTrashAlt,
    faTimes,
} from '@fortawesome/free-solid-svg-icons';

const getFileIcon = (filename: string) => {
    const ext = filename.split('.').pop()?.toLowerCase();
    switch (ext) {
        case 'jar':
        case 'java':
        case 'class':
            return { icon: faCube, color: '#dd9754' };
        case 'zip':
        case 'tar':
        case 'gz':
        case 'rar':
        case '7z':
            return { icon: faFileArchive, color: '#BFA89E' };
        case 'json':
        case 'yml':
        case 'yaml':
        case 'properties':
        case 'toml':
        case 'conf':
        case 'config':
        case 'env':
        case 'txt':
        case 'log':
            return { icon: faFileCode, color: '#60a5fa' };
        default:
            return { icon: faFileAlt, color: '#BFA89E' };
    }
};

const Spinner = ({ progress, size = 18 }: { progress: number; size?: number }) => {
    const safeProgress = Math.min(100, Math.max(0, isNaN(progress) ? 0 : progress));
    const strokeWidth = 2.5;
    const radius = (size - strokeWidth) / 2;
    const circumference = 2 * Math.PI * radius;
    const strokeDashoffset = ((100 - safeProgress) / 100) * circumference;

    return (
        <svg
            width={size}
            height={size}
            viewBox={`0 0 ${size} ${size}`}
            style={{
                width: `${size}px`,
                height: `${size}px`,
                minWidth: `${size}px`,
                minHeight: `${size}px`,
                maxWidth: `${size}px`,
                maxHeight: `${size}px`,
                display: 'block',
                flexShrink: 0,
            }}
        >
            <circle
                cx={size / 2}
                cy={size / 2}
                r={radius}
                stroke={'rgba(82, 183, 136, 0.2)'}
                strokeWidth={strokeWidth}
                fill={'none'}
            />
            <circle
                cx={size / 2}
                cy={size / 2}
                r={radius}
                stroke={'#52b788'}
                strokeWidth={strokeWidth}
                fill={'none'}
                strokeDasharray={circumference}
                strokeDashoffset={strokeDashoffset}
                strokeLinecap={'round'}
                style={{
                    transform: 'rotate(-90deg)',
                    transformOrigin: '50% 50%',
                    transition: 'stroke-dashoffset 0.25s ease',
                }}
            />
        </svg>
    );
};

const FileUploadList = () => {
    const { close } = useContext(DialogWrapperContext);
    const cancelFileUpload = ServerContext.useStoreActions((actions) => actions.files.cancelFileUpload);
    const clearFileUploads = ServerContext.useStoreActions((actions) => actions.files.clearFileUploads);
    const uploads = ServerContext.useStoreState((state) =>
        Object.entries(state.files.uploads).sort(([a], [b]) => a.localeCompare(b))
    );

    const totalUploaded = uploads.reduce((acc, [, f]) => acc + f.loaded, 0);
    const totalSize = uploads.reduce((acc, [, f]) => acc + f.total, 0);
    const overallPercent = totalSize > 0 ? Math.round((totalUploaded / totalSize) * 100) : 0;

    return (
        <div style={{ marginTop: '16px' }}>
            {/* Top Overview Bento Card */}
            <div
                style={{
                    background: 'linear-gradient(135deg, rgba(82, 183, 136, 0.08) 0%, rgba(26, 23, 21, 0.8) 100%)',
                    border: '1px solid rgba(82, 183, 136, 0.22)',
                    borderRadius: '12px',
                    padding: '14px 16px',
                    marginBottom: '16px',
                }}
            >
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '10px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <div
                            style={{
                                width: '32px',
                                height: '32px',
                                borderRadius: '8px',
                                background: 'rgba(82, 183, 136, 0.15)',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                color: '#52b788',
                                flexShrink: 0,
                            }}
                        >
                            <CloudUploadIcon style={{ width: '18px', height: '18px' }} />
                        </div>
                        <div>
                            <div style={{ fontFamily: "'Outfit', sans-serif", fontSize: '14px', fontWeight: 600, color: '#EBF5EE' }}>
                                Total Upload Progress
                            </div>
                            <div style={{ fontFamily: "'JetBrains Mono', monospace", fontSize: '11px', color: '#8B786D' }}>
                                {uploads.length} {uploads.length === 1 ? 'file' : 'files'} in progress
                            </div>
                        </div>
                    </div>
                    <div style={{ textAlign: 'right' }}>
                        <div style={{ fontFamily: "'JetBrains Mono', monospace", fontSize: '15px', fontWeight: 700, color: '#52b788' }}>
                            {overallPercent}%
                        </div>
                        <div style={{ fontFamily: "'JetBrains Mono', monospace", fontSize: '11px', color: '#8B786D' }}>
                            {bytesToString(totalUploaded)} / {bytesToString(totalSize)}
                        </div>
                    </div>
                </div>

                {/* Overall Progress Bar Track */}
                <div style={{ height: '7px', background: 'rgba(255, 255, 255, 0.06)', borderRadius: '9999px', overflow: 'hidden' }}>
                    <div
                        style={{
                            width: `${Math.min(100, Math.max(0, overallPercent))}%`,
                            height: '100%',
                            background: overallPercent >= 100
                                ? '#52b788'
                                : 'linear-gradient(90deg, #52b788 0%, #74a892 100%)',
                            borderRadius: '9999px',
                            transition: 'width 0.25s ease',
                        }}
                    />
                </div>
            </div>

            {/* Uploading Files List */}
            <div
                style={{
                    maxHeight: '340px',
                    overflowY: 'auto',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '10px',
                    paddingRight: '4px',
                }}
            >
                {uploads.map(([name, file]) => {
                    const pct = file.total > 0 ? Math.round((file.loaded / file.total) * 100) : 0;
                    const meta = getFileIcon(name);
                    const isDone = pct >= 100;

                    return (
                        <div
                            key={name}
                            style={{
                                background: '#1c1917',
                                border: '1px solid rgba(191, 168, 158, 0.12)',
                                borderRadius: '10px',
                                padding: '12px 14px',
                                transition: 'all 0.15s ease',
                            }}
                        >
                            {/* Top info row: Icon, Name, Percentage, Cancel Button */}
                            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '10px', marginBottom: '8px' }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', minWidth: 0, flex: 1 }}>
                                    <FontAwesomeIcon icon={meta.icon} style={{ color: meta.color, fontSize: '14px', flexShrink: 0 }} />
                                    <span
                                        style={{
                                            fontFamily: "'JetBrains Mono', monospace",
                                            fontSize: '13px',
                                            color: '#EBF5EE',
                                            fontWeight: 500,
                                            overflow: 'hidden',
                                            textOverflow: 'ellipsis',
                                            whiteSpace: 'nowrap',
                                        }}
                                        title={name}
                                    >
                                        {name}
                                    </span>
                                </div>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexShrink: 0 }}>
                                    <span
                                        style={{
                                            fontFamily: "'JetBrains Mono', monospace",
                                            fontSize: '12px',
                                            fontWeight: 600,
                                            color: isDone ? '#52b788' : '#BFA89E',
                                        }}
                                    >
                                        {pct}%
                                    </span>
                                    {!isDone ? (
                                        <button
                                            type={'button'}
                                            onClick={() => cancelFileUpload(name)}
                                            title={`Cancel upload for ${name}`}
                                            style={{
                                                background: 'rgba(201, 75, 75, 0.1)',
                                                border: '1px solid rgba(201, 75, 75, 0.25)',
                                                color: '#e57373',
                                                borderRadius: '6px',
                                                width: '24px',
                                                height: '24px',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center',
                                                cursor: 'pointer',
                                                transition: 'all 0.15s ease',
                                            }}
                                            onMouseEnter={(e) => {
                                                e.currentTarget.style.background = 'rgba(201, 75, 75, 0.25)';
                                                e.currentTarget.style.color = '#ffffff';
                                            }}
                                            onMouseLeave={(e) => {
                                                e.currentTarget.style.background = 'rgba(201, 75, 75, 0.1)';
                                                e.currentTarget.style.color = '#e57373';
                                            }}
                                        >
                                            <FontAwesomeIcon icon={faTimes} style={{ fontSize: '11px' }} />
                                        </button>
                                    ) : (
                                        <FontAwesomeIcon icon={faCheckCircle} style={{ color: '#52b788', fontSize: '14px' }} />
                                    )}
                                </div>
                            </div>

                            {/* Linear Progress Bar */}
                            <div style={{ height: '5px', background: 'rgba(255, 255, 255, 0.06)', borderRadius: '9999px', overflow: 'hidden', marginBottom: '8px' }}>
                                <div
                                    style={{
                                        width: `${Math.min(100, Math.max(0, pct))}%`,
                                        height: '100%',
                                        background: isDone
                                            ? '#52b788'
                                            : 'linear-gradient(90deg, #52b788 0%, #74a892 100%)',
                                        borderRadius: '9999px',
                                        transition: 'width 0.25s ease',
                                    }}
                                />
                            </div>

                            {/* Bottom row: Bytes transferred & Status */}
                            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', fontSize: '11px', color: '#8B786D', fontFamily: "'JetBrains Mono', monospace" }}>
                                <span>
                                    {bytesToString(file.loaded)} / {bytesToString(file.total)}
                                </span>
                                <span>
                                    {isDone ? 'Processing...' : 'Uploading...'}
                                </span>
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Footer Buttons */}
            <Dialog.Footer>
                <button
                    type={'button'}
                    onClick={() => clearFileUploads()}
                    style={{
                        background: 'rgba(201, 75, 75, 0.12)',
                        border: '1px solid rgba(201, 75, 75, 0.35)',
                        color: '#e57373',
                        fontFamily: "'Outfit', sans-serif",
                        fontSize: '13px',
                        fontWeight: 500,
                        padding: '8px 16px',
                        borderRadius: '8px',
                        cursor: 'pointer',
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: '6px',
                        transition: 'all 0.15s ease',
                    }}
                    onMouseEnter={(e) => {
                        e.currentTarget.style.background = 'rgba(201, 75, 75, 0.25)';
                        e.currentTarget.style.color = '#ffffff';
                    }}
                    onMouseLeave={(e) => {
                        e.currentTarget.style.background = 'rgba(201, 75, 75, 0.12)';
                        e.currentTarget.style.color = '#e57373';
                    }}
                >
                    <FontAwesomeIcon icon={faTrashAlt} style={{ fontSize: '11px' }} />
                    <span>Cancel All Uploads</span>
                </button>
                <button
                    type={'button'}
                    onClick={close}
                    style={{
                        background: '#25211e',
                        border: '1px solid rgba(139, 120, 109, 0.35)',
                        color: '#EBF5EE',
                        fontFamily: "'Outfit', sans-serif",
                        fontSize: '13px',
                        fontWeight: 500,
                        padding: '8px 18px',
                        borderRadius: '8px',
                        cursor: 'pointer',
                        transition: 'all 0.15s ease',
                    }}
                    onMouseEnter={(e) => {
                        e.currentTarget.style.background = '#2e2925';
                        e.currentTarget.style.borderColor = '#BFA89E';
                    }}
                    onMouseLeave={(e) => {
                        e.currentTarget.style.background = '#25211e';
                        e.currentTarget.style.borderColor = 'rgba(139, 120, 109, 0.35)';
                    }}
                >
                    Close
                </button>
            </Dialog.Footer>
        </div>
    );
};

const FileUploadListDialog = asDialog({
    title: 'File Uploads',
    description: 'Monitor and manage files currently being uploaded to your server.',
})(FileUploadList);

export default () => {
    const open = useSignal(false);

    const count = ServerContext.useStoreState((state) => Object.keys(state.files.uploads).length);
    const progress = ServerContext.useStoreState((state) => ({
        uploaded: Object.values(state.files.uploads).reduce((acc, file) => acc + file.loaded, 0),
        total: Object.values(state.files.uploads).reduce((acc, file) => acc + file.total, 0),
    }));

    const totalPercent = progress.total > 0 ? (progress.uploaded / progress.total) * 100 : 0;

    useEffect(() => {
        if (count === 0) {
            open.value = false;
        }
    }, [count]);

    return (
        <>
            {count > 0 && (
                <Tooltip content={`${count} files uploading. Click to view progress.`}>
                    <button
                        type={'button'}
                        onClick={() => (open.value = true)}
                        style={{
                            background: 'linear-gradient(135deg, rgba(82, 183, 136, 0.15) 0%, rgba(82, 183, 136, 0.08) 100%)',
                            border: '1px solid rgba(82, 183, 136, 0.4)',
                            color: '#52b788',
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '8px',
                            padding: '8px 14px',
                            borderRadius: '8px',
                            cursor: 'pointer',
                            transition: 'all 0.15s ease',
                        }}
                    >
                        <div style={{ position: 'relative', width: '18px', height: '18px', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                            <Spinner progress={totalPercent} size={18} />
                            <CloudUploadIcon style={{ width: '10px', height: '10px', position: 'absolute', color: '#52b788' }} />
                        </div>
                        <span style={{ fontWeight: 600, fontFamily: "'Outfit', sans-serif" }}>Uploading ({count})</span>
                        <span
                            style={{
                                fontFamily: "'JetBrains Mono', monospace",
                                fontSize: '11px',
                                background: 'rgba(82, 183, 136, 0.18)',
                                padding: '2px 6px',
                                borderRadius: '4px',
                                color: '#52b788',
                                fontWeight: 600,
                            }}
                        >
                            {Math.round(totalPercent)}%
                        </span>
                    </button>
                </Tooltip>
            )}
            <FileUploadListDialog open={open.value} onClose={() => (open.value = false)} />
        </>
    );
};
