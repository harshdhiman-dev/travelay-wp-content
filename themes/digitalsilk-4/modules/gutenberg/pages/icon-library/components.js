import { __ } from '@wordpress/i18n';
import { FormFileUpload, Spinner, Icon, ProgressBar } from '@wordpress/components';
// eslint-disable-next-line import/no-extraneous-dependencies
import { plus } from '@wordpress/icons';

// Drag&Drop Zone component.
export const DragDropZone = ({
    isUploading,
    doFileUpload,
    termId,
    termSlug
}) => {

    return (
        <FormFileUpload
            accept="image/*"
            multiple
            onChange={ (event) => doFileUpload( event.target.files, termId, termSlug ) }
            variant='link'
            disabled={isUploading}
            size='compact'
            className='iconList__item -uploader'
        >
            <span className="iconList__link">
            {isUploading ? (
                <>
                    <Spinner />
                    <span className='iconList__title'>{__('Loading...')}</span>
                </>
            ) : (
                <>
                    <Icon
                        icon={plus}
                    />
                    <span className='iconList__title'>{__('Add New')}</span>
                </>
            )}
            </span>
        </FormFileUpload>
    );
};

// Icon placeholder.
export const IconPlaceholder = () => {
    return (
        <li className='iconList__item -placeholder'>
            <span className='iconList__link'>
                <span className='progress'>
                    <ProgressBar />
                </span>
                <span className='iconList__title'>{__('Loading...')}</span>
            </span>
        </li>
    );
}