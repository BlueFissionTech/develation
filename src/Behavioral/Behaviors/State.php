<?php
namespace BlueFission\Behavioral\Behaviors;

/**
 * Class State
 *
 * A class that represents a state behavior in a behavioral model.
 *
 * @package BlueFission\Behavioral\Behaviors
 */
class State extends Behavior
{
	const DRAFT = 'IsDraft';
	const DONE = 'IsDone';
	const NORMAL = 'IsNormal';
	const READONLY = 'IsReadonly';
	const BUSY = 'IsBusy';
	const IDLE = 'IsIdle';
    const LOADING = 'IsLoading';
    const SAVING = 'IsSaving';
    const EDITING = 'IsEditing';
    const VIEWING = 'IsViewing';
    const PENDING = 'IsPending';
    const APPROVED = 'IsApproved';
    const REJECTED = 'IsRejected';
    const FULFILLED = 'IsFulfilled';
    const ARCHIVED = 'IsArchived';

    // State changes
    const STATE_CHANGING = 'IsChangingState';

    // CRUD Operations
    const CREATING = 'IsCreating';
    const READING = 'IsReading';
    const UPDATING = 'IsUpdating';
    const DELETING = 'IsDeleting';

    // Authentication and Authorization
    const AUTHENTICATING = 'IsAuthenticating';
    const AUTHENTICATED = 'IsAuthenticated';
    const UNAUTHENTICATED = 'IsUnauthenticated';
    const AUTHORIZATION_GRANTED = 'IsAuthorizationGranted';
    const AUTHORIZATION_DENIED = 'IsAuthorizationDenied';
    const SESSION_STARTING = 'IsStartingSession';
    const SESSION_ENDING = 'IsEndingSession';


    // Network and Communication
    const CONNECTING = 'IsConnecting';
    const CONNECTED = 'IsConnected';
    const DISCONNECTING = 'IsDisconnecting';
    const DISCONNECTED = 'IsDisconnected';

    // Data State
    const SYNCING = 'IsSyncing';
    const SYNCED = 'IsSynced';
    const OUT_OF_SYNC = 'IsOutOfSync';
    const SENDING = 'IsSending';
    const RECEIVING = 'IsReceiving';

    // Operational
    const OPERATIONAL = 'IsOperational';
    const NON_OPERATIONAL = 'IsNonOperational';
    const MAINTENANCE = 'IsMaintenance';
    const DEGRADED = 'IsDegraded';
    const FAILURE = 'IsFailure';

    // User Interaction
    const INTERACTING = 'IsInteracting';
    const NON_INTERACTIVE = 'IsNonInteractive';

    // Custom application states
    const CONFIGURING = 'IsConfiguring';
    const INITIALIZING = 'IsInitializing';
    const FINALIZING = 'IsFinalizing';
    const PROCESSING = 'IsProcessing';
    const WAITING_FOR_INPUT = 'IsWaitingForInput';
    const PERFORMING_ACTION = 'IsPerformingAction';
    const ACTION_COMPLETED = 'IsActionCompleted';
    const ERROR_STATE = 'IsErrorState';

	/**
	 * State constructor.
	 *
	 * @param string $name The name of the state behavior.
	 */
	public function __construct( $name )
	{
		parent::__construct( $name, 0, true, true );
	}
}
