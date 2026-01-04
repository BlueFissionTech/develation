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
    public const DRAFT = 'IsDraft';
    public const DONE = 'IsDone';
    public const NORMAL = 'IsNormal';
    public const READONLY = 'IsReadonly';
    public const BUSY = 'IsBusy';
    public const IDLE = 'IsIdle';
    public const LOADING = 'IsLoading';
    public const SAVING = 'IsSaving';
    public const EDITING = 'IsEditing';
    public const VIEWING = 'IsViewing';
    public const PENDING = 'IsPending';
    public const APPROVED = 'IsApproved';
    public const REJECTED = 'IsRejected';
    public const FULFILLED = 'IsFulfilled';
    public const ARCHIVED = 'IsArchived';
    public const RUNNING = 'IsRunning';
    public const CHANGING = 'IsChanging';

    // State changes
    public const STATE_CHANGING = 'IsChangingState';

    // CRUD Operations
    public const CREATING = 'IsCreating';
    public const READING = 'IsReading';
    public const UPDATING = 'IsUpdating';
    public const DELETING = 'IsDeleting';

    // Authentication and Authorization
    public const AUTHENTICATING = 'IsAuthenticating';
    public const AUTHENTICATED = 'IsAuthenticated';
    public const UNAUTHENTICATED = 'IsUnauthenticated';
    public const AUTHORIZATION_GRANTED = 'IsAuthorizationGranted';
    public const AUTHORIZATION_DENIED = 'IsAuthorizationDenied';
    public const SESSION_STARTING = 'IsStartingSession';
    public const SESSION_ENDING = 'IsEndingSession';


    // Network and Communication
    public const CONNECTING = 'IsConnecting';
    public const CONNECTED = 'IsConnected';
    public const DISCONNECTING = 'IsDisconnecting';
    public const DISCONNECTED = 'IsDisconnected';

    // Data State
    public const SYNCING = 'IsSyncing';
    public const SYNCED = 'IsSynced';
    public const OUT_OF_SYNC = 'IsOutOfSync';
    public const SENDING = 'IsSending';
    public const RECEIVING = 'IsReceiving';

    // Operational
    public const OPERATIONAL = 'IsOperational';
    public const NON_OPERATIONAL = 'IsNonOperational';
    public const MAINTENANCE = 'IsMaintenance';
    public const DEGRADED = 'IsDegraded';
    public const FAILURE = 'IsFailure';

    // User Interaction
    public const INTERACTING = 'IsInteracting';
    public const NON_INTERACTIVE = 'IsNonInteractive';

    // Custom application states
    public const CONFIGURING = 'IsConfiguring';
    public const INITIALIZING = 'IsInitializing';
    public const FINALIZING = 'IsFinalizing';
    public const PROCESSING = 'IsProcessing';
    public const STOPPED = 'IsStopped';
    public const WAITING_FOR_INPUT = 'IsWaitingForInput';
    public const PERFORMING_ACTION = 'IsPerformingAction';
    public const ACTION_COMPLETED = 'IsActionCompleted';
    public const ERROR_STATE = 'IsErrorState';

    /**
     * State constructor.
     *
     * @param string $name The name of the state behavior.
     */
    public function __construct($name)
    {
        parent::__construct($name, 0, true, true);
    }
}
