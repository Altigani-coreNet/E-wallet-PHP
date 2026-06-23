import './bootstrap';
import { createRoot } from 'react-dom/client';
import TerminalGroupForm from './components/TerminalGroupForm';
import TerminalGroupEditFormWrapper from './components/TerminalGroupEditFormWrapper';
import UserGroupForm from './components/UserGroupForm';
import UserGroupEditForm from './components/UserGroupEditForm';
import MerchantUserGroupForm from './components/MerchantUserGroupForm';
import MerchantUserGroupEditForm from './components/MerchantUserGroupEditForm';
import MerchantRegister from './components/MerchantRegister';
import ForgotPasswordCard from './components/ForgotPasswordCard';
import Profile from './components/Profile/Profile';
import ProfileHeader from './components/Profile/ProfileHeader';
import Overview from './components/Profile/Overview';
import ProfileInfo from './components/Profile/ProfileInfo';
import ProfileEdit from './components/Profile/ProfileEdit';
import ChangePassword from './components/Profile/ChangePassword';

// Mount React components when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Mount TerminalGroupForm if the element exists (create page)
    const terminalGroupFormElement = document.getElementById('terminal-group-form-root');
    
    if (terminalGroupFormElement) {
        try {
            const root = createRoot(terminalGroupFormElement);
            root.render(<TerminalGroupForm />);
        } catch (error) {
            console.error('Error mounting TerminalGroupForm component:', error);
        }
    }

    // Mount TerminalGroupEditFormWrapper if the element exists (edit page)
    const terminalGroupEditFormElement = document.getElementById('terminal-group-edit-form-root');
    
    if (terminalGroupEditFormElement) {
        try {
            const root = createRoot(terminalGroupEditFormElement);
            root.render(<TerminalGroupEditFormWrapper />);
        } catch (error) {
            console.error('Error mounting TerminalGroupEditFormWrapper component:', error);
        }
    }

    // Mount UserGroupForm if the element exists (user groups create page)
    const userGroupFormElement = document.getElementById('user-group-form-root');
    if (userGroupFormElement) {
        try {
            const root = createRoot(userGroupFormElement);
            root.render(<UserGroupForm />);
        } catch (error) {
            console.error('Error mounting UserGroupForm component:', error);
        }
    }

    // Mount UserGroupEditForm if the element exists (user groups edit page)
    const userGroupEditFormElement = document.getElementById('user-group-edit-form-root');
    if (userGroupEditFormElement) {
        try {
            const root = createRoot(userGroupEditFormElement);
            root.render(<UserGroupEditForm />);
        } catch (error) {
            console.error('Error mounting UserGroupEditForm component:', error);
        }
    }

    // Mount MerchantUserGroupForm if the element exists (merchant-specific user groups create page)
    const merchantUserGroupFormElement = document.getElementById('merchant-user-group-form-root');
    if (merchantUserGroupFormElement) {
        try {
            // Get merchant ID from data attribute
            const merchantId = merchantUserGroupFormElement.getAttribute('data-merchant-id');
            
            const root = createRoot(merchantUserGroupFormElement);
            root.render(<MerchantUserGroupForm merchantId={merchantId} />);
        } catch (error) {
            console.error('Error mounting MerchantUserGroupForm component:', error);
        }
    }

    // Mount MerchantUserGroupEditForm if the element exists (merchant-specific user groups edit page)
    const merchantUserGroupEditFormElement = document.getElementById('merchant-user-group-edit-form-root');
    if (merchantUserGroupEditFormElement) {
        try {
            // Get merchant ID and user group data from data attributes
            const merchantId = merchantUserGroupEditFormElement.getAttribute('data-merchant-id');
            const userGroupData = merchantUserGroupEditFormElement.getAttribute('data-user-group');
            
            let userGroup = null;
            if (userGroupData) {
                try {
                    userGroup = JSON.parse(userGroupData);
                } catch (e) {
                    console.error('Error parsing user group data:', e);
                }
            }
            
            const root = createRoot(merchantUserGroupEditFormElement);
            root.render(<MerchantUserGroupEditForm userGroup={userGroup} merchantId={merchantId} />);
        } catch (error) {
            console.error('Error mounting MerchantUserGroupEditForm component:', error);
        }
    }

    // Mount MerchantRegister if the element exists
    const merchantRegisterElement = document.getElementById('merchant-register-root');
    if (merchantRegisterElement) {
        try {
            const root = createRoot(merchantRegisterElement);
            root.render(<MerchantRegister />);
        } catch (error) {
            console.error('Error mounting MerchantRegister component:', error);
        }
    }

    // Mount ForgotPasswordCard if the element exists
    const forgotPasswordRoot = document.getElementById('merchant-forgot-password-root');
    if (forgotPasswordRoot) {
        try {
            const root = createRoot(forgotPasswordRoot);
            root.render(<ForgotPasswordCard />);
        } catch (error) {
            console.error('Error mounting ForgotPasswordCard component:', error);
        }
    }

    // Mount Profile component if the element exists
    const profileRoot = document.getElementById('profile-root');
    if (profileRoot) {
        try {
            const root = createRoot(profileRoot);
            root.render(<Profile />);
        } catch (error) {
            console.error('Error mounting Profile component:', error);
        }
    }

    // Mount ProfileInfo component if the element exists
    const profileInfoRoot = document.getElementById('profile-info-root');
    if (profileInfoRoot) {
        try {
            const root = createRoot(profileInfoRoot);
            root.render(<ProfileInfo />);
        } catch (error) {
            console.error('Error mounting ProfileInfo component:', error);
        }
    }

    // Mount ProfileEdit component if the element exists
    const profileEditRoot = document.getElementById('profile-edit-root');
    if (profileEditRoot) {
        try {
            const root = createRoot(profileEditRoot);
            root.render(<ProfileEdit />);
        } catch (error) {
            console.error('Error mounting ProfileEdit component:', error);
        }
    }

    // Mount ChangePassword component if the element exists
    const changePasswordRoot = document.getElementById('change-password-root');
    if (changePasswordRoot) {
        try {
            const root = createRoot(changePasswordRoot);
            root.render(<ChangePassword />);
        } catch (error) {
            console.error('Error mounting ChangePassword component:', error);
        }
    }

}); 