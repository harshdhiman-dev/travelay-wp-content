import { __ } from '@wordpress/i18n';
import {
    useState,
    useEffect,
    createRoot,
} from '@wordpress/element';
import {
    Button,
    Icon,
    Card,
    CardBody,
    Notice,
    Spinner,
    __experimentalTruncate as Truncate,
    Snackbar,
    Animate,
    Tooltip,
    TabPanel,
    Icon as IconComponent,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { normalizeName } from './utilities';
import { DragDropZone, IconPlaceholder } from './components';
// eslint-disable-next-line import/no-extraneous-dependencies
import { cloudUpload, globe } from '@wordpress/icons';
import './style.scss';

const DsIconLibrary = () => {
    const [ iconsList, setIconsList ] = useState({});
    const [ initialLoading, setInitialLoading ] = useState(true);
    const [ isLoading, setIsLoading ] = useState(true);
    const [ incomingIcons, setIncomingIcons ] = useState({});
    const [ isUploading, setIsUploading ] = useState(false);
    const [ isDeleting, setIsDeleting ] = useState(false);
    const [ isDeleted, setIsDeleted ] = useState(false);
    const [ workingError, setWorkingError ] = useState(null);
    const [ workingSuccess, setWorkingSuccess ] = useState(null);
    const [ dragging, setDragging ] = useState(false);
    const [ snackVisibe, setSnackVisibe ] = useState( false );
    const [ availableThemeIcons, setAvailableThemeIcons ] = useState( [] );

    // Extract constants from our local data store.
    const { iconLibraryDataStore } = window;
    const { taxonomyTerms } = iconLibraryDataStore;

    // Fetch the attachments on page load
    useEffect(
        () => {
            // Fetch icons for each category initially.
            Object.keys(taxonomyTerms).forEach(
                ( termSlug ) => {
                    fetchIcons(termSlug);
                }
            );
            // Fetch available theme icons.
            fetchThemeIcons();
        },
        // This effect should only run once on mount, so we pass an empty array.
        // eslint-disable-next-line react-hooks/exhaustive-deps
        []
    );

    // Set snackbar visibility.
    useEffect(
        () => {
            // If snackbar is visible and there was no error, hide it after 4 seconds.
            if ( snackVisibe && ! workingError ) {
                const timer = setTimeout(
                    () => {
                        setSnackVisibe(false);
                    },
                    4000
                );
        
                // Cleanup the timeout if the component unmounts or snackVisibe changes
                return () => clearTimeout(timer);
            }
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [ snackVisibe ]
    );

    // Fetch theme icons.
    const fetchThemeIcons = () => {
        // Fetch available theme icons.
        apiFetch(
            {
                path: '/ds/v1/icons-svg',
            }
        ).then(
            (response) => {
                setAvailableThemeIcons(response);
            }
        ).catch(
            (error) => {
                // eslint-disable-next-line no-console
                console.warn('Error fetching icons:', error);
            }
        );
    }

    // Function to set incoming icons for a specific termSlug
    const setIncomingIconsForSlug = ( termSlug, count ) => {
        setIncomingIcons(
            (prevState) => (
                {
                    ...prevState,
                    [termSlug]: count,
                }
            )
        );
    };

    // Function to get the number of incoming icons for a specific termSlug
    const getIncomingIconsForSlug = (termSlug) => {
        if (
            // eslint-disable-next-line no-prototype-builtins
            incomingIcons.hasOwnProperty(termSlug) &&
            Number.isInteger(incomingIcons[termSlug]) &&
            incomingIcons[termSlug] > 0
        ) {
            return incomingIcons[termSlug];
        }
        return 0;
    };

    // Fetch the icons from our REST API endpoint.
    const fetchIcons = (termSlug) => {
        // Set loading state.
        setIsLoading(true);

        // Set query params
        const queryParams = { tax: termSlug };

        // Do the fetch.
        apiFetch(
            {
                path: addQueryArgs('ds/v1/icons-media', queryParams),
                method: 'GET',
            }
        ).then(
            (response) => {
                // Update the state to include icons categorized by termSlug.
                setIconsList(
                    (prevIconsList) => (
                        {
                            ...prevIconsList,
                            [termSlug]: response,
                        }
                    )
                );
                // Remove any icons deleted state.
                setIsDeleted(false);
                // Unset loading states.
                setIsLoading(false);
                setInitialLoading(false);
                // Remove incoming icons state.
                setIncomingIconsForSlug(termSlug, 0);
            }
        ).catch(
            (error) => {
                // eslint-disable-next-line no-console
                console.warn('Error:', error);
                // Remove any icons deleted state.
                setIsDeleted(false);
                // Unset loading states.
                setIsLoading(false);
                setInitialLoading(false);
                // Remove incomming icons state.
                setIncomingIconsForSlug(termSlug, 0);
            }
        );
    };


    // Handle file uploads with a term ID.
    const doFileUpload = async (files, termId, termSlug) => {
        // Set initial states for uploading.
        setIsLoading(true);
        setIsUploading(termId);
        setWorkingError(null);
        setWorkingSuccess(null);

        // Convert FileList to an array and filter only image files (including SVG)
        const fileArray = Array.from(files).filter(
            (file) => {
                const validImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml'];
                // Exclude non-image files
                if ( ! validImageTypes.includes( file.type ) ) {
                    setWorkingError(`File "${file.name}" is not a valid image file. Only images and SVG files are allowed.`);
                    // Show snackbar with error
                    setSnackVisibe(true);
                    return false;
                }
                return true;
            }
        );

        // Exit if no valid files are left after filtering
        if ( fileArray.length === 0 ) {
            setIsUploading(false);
            return; 
        }

        // Set incoming icons state.
        setIncomingIconsForSlug(termSlug, fileArray.length);

        // Upload and categorize files with the given term ID.
        try {
            const responses = await Promise.all(
                fileArray.map(
                    (file) => uploadAndCategorizeFile(file, termId)
                )
            );
            // If all uploads and taxonomy assignments succeed
            setWorkingSuccess(`${responses.length} icon(s) uploaded successfully!`);
            // Fetch the icons again to show uploaded icons
            fetchIcons( termSlug );
        } catch (error) {
            // eslint-disable-next-line no-console
            console.warn('Upload Error:', error);
            // Set error message.
            setWorkingError('An error occurred during the upload. Please try again.');
        } finally {
            // Show notification.
            setSnackVisibe(true);
            // Finish uploading state
            setIsUploading(false);
        }
    };

    // Upload the file and categorize it using the provided term ID.
    const uploadAndCategorizeFile = (file, termId) => {
        // We must use the form data, because REST API expects it.
        const formData = new FormData();
        formData.append('file', file);

        // Upload the file using apiFetch POST
        return apiFetch(
            {
                path: '/wp/v2/media',
                method: 'POST',
                body: formData,
                headers: {
                    'Content-Disposition': `attachment; filename="${file.name}"`,
                },
            }
        ).then(
            (uploadResponse) => {
                // After successful upload, categorize the file.
                return categorizeFile(uploadResponse.id, termId);
            }
        ).catch(
            (error) => {
                // eslint-disable-next-line no-console
                console.warn('Error during upload or taxonomy assignment:', error);
                throw error; // Re-throw the error to handle it in the parent Promise
            }
        );
    };

    // Assign the uploaded file to the provided term ID in the "icon_type" taxonomy.
    const categorizeFile = (fileId, termId) => {
        return apiFetch(
            {
                path: `/wp/v2/media/${fileId}`,
                method: 'PUT',
                body: JSON.stringify(
                    {
                        icon_type: [termId],
                    }
                ),
                headers: {
                    'Content-Type': 'application/json',
                },
            }
        ).catch(
            (error) => {
                // eslint-disable-next-line no-console
                console.warn('Error during categorization:', error);
                throw error; // Re-throw the error to handle it in the parent Promise.
            }
        );
    };

    // Function to change the category of an existing attachment
    const changeAttachmentCategory = (
        imageId,
        currentCategorySlug,
        termId,
        termSlug
    ) => {
        // Unset working error & success
        setWorkingError(false);
        setWorkingSuccess(false);

        // Set loading state.
        setIsLoading(true);

        // Stop if the new slug and the current slug are identical
        if ( termSlug === currentCategorySlug ) {
            return;
        }

        // Set incoming icons state to 1.
        setIncomingIconsForSlug(termSlug, getIncomingIconsForSlug(termSlug) + 1);

        // Set original icon as deleted.
        setIsDeleted( imageId );

        // Call categorizeFile to update the image's category
        categorizeFile(
            imageId, termId
        ).then(
            () => {
                /**
                 * Refetch icons for both the old and new categories to update the UI
                 */
                // Fetch icons for the new category
                fetchIcons(termSlug); 
                // Fetch icons for the old category
                fetchIcons(currentCategorySlug);
                // Set success message.
                setWorkingSuccess(`Image ID ${imageId} moved from "${normalizeName(currentCategorySlug)}" to "${normalizeName(termSlug)}" successfully.`);
            }
        ).catch(
            (error) => {
                // eslint-disable-next-line no-console
                console.warn('Error categorizing image:', error);
                // Set error message.
                setWorkingError('An error occurred while changing the category.');
            }
        ).finally(
            () => {
                // Show notice.
                setSnackVisibe(true);
            }
        );
    };

    // Delete an attachment.
    const deleteAttachment = async (attachmentId, termSlug) => {
        setSnackVisibe(false);
        setIsDeleting(attachmentId);
        setWorkingError(null);
        setWorkingSuccess(null);
        try {
            // Make an API call to delete the attachment
            await apiFetch(
                {
                    path: `/wp/v2/media/${attachmentId}`,
                    method: 'DELETE',
                    data: { force: true },
                }
            );
    
            // Refresh icons after deletion.
            fetchIcons( termSlug );
            setWorkingSuccess(`Attachment with ID ${attachmentId} deleted successfully.`);
        } catch (error) {
            // eslint-disable-next-line no-console
            console.warn('Error deleting attachment:', error);
            setWorkingError('An error occurred while deleting the attachment.')
        } finally {
            // Set that icon is deleted.
            setIsDeleted( attachmentId );
            // Show notification.
            setSnackVisibe(true);
            // Finish deleting state
            setIsDeleting(false);
        }
    };

    // Handle drag over event
    const handleUploaderDragOver = (event, termId) => {
        event.preventDefault();
        setDragging(termId);
    };

    // Handle drag leave event
    const handleUploaderDragLeave = () => {
        setDragging(false);
    };

    // Function to handle the drop event
    const handleUploaderDragDrop = (event, termId, termSlug) => {
        event.preventDefault();
        setDragging(false);

        // Check if files are being dropped
        const files = event.dataTransfer.files;

        if (files.length > 0) {
            // Handle file upload as before
            doFileUpload(files, termId, termSlug);
        } else {
            // Check if an image ID is present in the dataTransfer object
            const imageId = event.dataTransfer.getData('imageId');
            const currentCategorySlug = event.dataTransfer.getData('currentCategorySlug');

            if (imageId && currentCategorySlug) {
                // Call the new function to change the attachment category
                changeAttachmentCategory(imageId, currentCategorySlug, termId, termSlug);
            }
        }
    };
    return (
        <>
            <TabPanel
                className="ds-icon-library-tabs"
                tabs={
                    [
                        {
                            name: 'icon-library',
                            title: (
                                <span style={{ display: 'flex', alignItems: 'center', gap: '2px' }}>
                                    <IconComponent icon={cloudUpload} />
                                    { __( 'Icon Library' ) }
                                </span>
                            ),
                        },
                        {
                            name: 'theme-icons',
                            title: (
                                <span style={{ display: 'flex', alignItems: 'center', gap: '2px' }}>
                                    <IconComponent icon={globe} size={20} />
                                    { __( 'Theme Icons' ) }
                                </span>
                            ),
                        },
                    ]
                }
            >
                {
                    ( tab ) => (
                        'icon-library' === tab.name ? (
                            <Card elevation={2} isRounded={false}>
                                <CardBody>
                                    {/* File Upload Section with Drag-and-Drop */}
                                        {
                                            snackVisibe && (
                                                <Animate type="appear" options={ { origin: 'top right' } }>
                                                    { ( { className } ) => (
                                                        <Snackbar
                                                            explicitDismiss
                                                            onDismiss={ () => setSnackVisibe(false) }
                                                            onRemove={ () => setSnackVisibe(false) }
                                                            className={`snackbarNotification ${className}`}
                                                        >
                                                            {
                                                                workingError && (
                                                                    <>{workingError}</>
                                                                )
                                                            }
                                                            {
                                                                workingSuccess && (
                                                                    <>{workingSuccess}</>
                                                                )
                                                            }
                                                        </Snackbar>
                                                    ) }
                                                </Animate>
                                            )
                                        }
                                        {
                                            taxonomyTerms && (
                                                <>
                                                    {
                                                        Object.entries(taxonomyTerms).map(
                                                            ([name, id]) => (
                                                                <div
                                                                    key={id}
                                                                    className={`iconDropZone ${id === dragging ? '-dragging' : ''}`}
                                                                    onDragOver={(event) => handleUploaderDragOver(event, id)}
                                                                    onDragLeave={handleUploaderDragLeave}
                                                                    onDrop={(event) => handleUploaderDragDrop(event, id, name)}
                                                                >
                                                                    <h3>{normalizeName(name)}</h3>
                                                                    <div className="iconWrapper">
                                                                        <ul className='iconList'>
                                                                            {/* Set incoming icons placeholder */}
                                                                            {
                                                                                isLoading &&
                                                                                getIncomingIconsForSlug(name) > 0 && (
                                                                                    Array.from({ length: getIncomingIconsForSlug(name) }).map(
                                                                                        (_, index) => (
                                                                                            <IconPlaceholder key={index} />
                                                                                        )
                                                                                    )
                                                                                )
                                                                            }
                                                                            {/* Display icons for this category */}
                                                                            {initialLoading && !iconsList[name] ? (
                                                                                <Spinner />
                                                                            ) : (
                                                                                <>
                                                                                    {iconsList[name] && iconsList[name].length > 0 && (
                                                                                        iconsList[name].map(
                                                                                            (item) => (
                                                                                                <li
                                                                                                    key={item.id}
                                                                                                    className={`iconList__item ${item.id === isDeleting ? 'loading' : ''} ${item.id === isDeleted ? 'deleted' : ''}`}
                                                                                                    draggable
                                                                                                    onDragStart={
                                                                                                        (event) => {
                                                                                                            event.dataTransfer.setData('imageId', item.id);
                                                                                                            event.dataTransfer.setData('currentCategorySlug', name);
                                                                                                        }
                                                                                                    }
                                                                                                >
                                                                                                    <Tooltip text={item.name}>
                                                                                                        <span className="iconList__link">
                                                                                                            <img
                                                                                                                src={item.url}
                                                                                                                alt={item.name}
                                                                                                                width={32}
                                                                                                                height={32}
                                                                                                                className='iconList__img'
                                                                                                            />
                                                                                                            <Truncate
                                                                                                                numberOfLines={1}
                                                                                                                className='iconList__title'
                                                                                                            >
                                                                                                                {item.name}
                                                                                                            </Truncate>
                                                                                                            <Button
                                                                                                                onClick={() => deleteAttachment(item.id, name)}
                                                                                                                variant='link'
                                                                                                                size='small'
                                                                                                                isDestructive
                                                                                                                className='iconList__button'
                                                                                                                disabled={isDeleting && item.id === isDeleting}
                                                                                                            >
                                                                                                                <Icon icon='trash' />
                                                                                                            </Button>
                                                                                                        </span>
                                                                                                    </Tooltip>
                                                                                                </li>
                                                                                            )
                                                                                        )
                                                                                    )}
                                                                                </>
                                                                            )}
                                                                            {
                                                                                ! initialLoading && (
                                                                                    <DragDropZone
                                                                                        isUploading={isUploading === id}
                                                                                        doFileUpload={(files) => doFileUpload(files, id, name)} // For direct file upload handling
                                                                                        termId={id}
                                                                                        termSlug={name}
                                                                                    />
                                                                                )
                                                                            }
                
                                                                        </ul>
                                                                    </div>
                                                                    <div className='iconDropPlaceholder'>
                                                                        <div className='iconDropPlaceholder__content'>
                                                                            <p>{`Drop files to upload to the ${normalizeName(name)} category`}</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            )
                                                        )
                                                    }
                                                </>
                                            )
                                        }
                                </CardBody>
                            </Card>
                        ) : (
                            availableThemeIcons && availableThemeIcons.length > 0 && (
                                <Card elevation={2} isRounded={false}>
                                    <CardBody>
                                        <Notice
                                            status='success'
                                            isDismissible={false}
                                        >
                                            {__('These icons are built into your theme for consistent styling and cannot be removed. Use them to maintain a unified look and feel across your site.')}
                                        </Notice>
                                        <div className='iconWrapper'>
                                            <ul className='iconList'>
                                                {
                                                    availableThemeIcons.map(
                                                        (item) => (
                                                            <li
                                                                key={item.id}
                                                                className='iconList__item -static'
                                                            >
                                                                <Tooltip text={item.name}>
                                                                    <span className="iconList__link">
                                                                        <img
                                                                            src={item.url}
                                                                            alt={item.name}
                                                                            width={32}
                                                                            height={32}
                                                                            className='iconList__img'
                                                                        />
                                                                        <Truncate
                                                                            numberOfLines={1}
                                                                            className='iconList__title'
                                                                        >
                                                                            {item.name}
                                                                        </Truncate>
                                                                    </span>
                                                                </Tooltip>
                                                            </li>
                                                        )
                                                    )
                                                }
                                            </ul>
                                        </div>
                                    </CardBody>
                                </Card>
                            )
                        )
                    )
                }
            </TabPanel>
        </>
    );
};

/**
 * Render the component to a specific div in our admin page.
 * ID's must match in order for the component to be rendered.
 * ID is set in core/icon-library/class-ds-icon-library->icon_library_page_html() method.
 */
document.addEventListener('DOMContentLoaded', () => {
    const target = document.getElementById('ds-icon-library-app');
    if (target) {
        const root = createRoot(target);
        root.render(<DsIconLibrary />);
    }
});