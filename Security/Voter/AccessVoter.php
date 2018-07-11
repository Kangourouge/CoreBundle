<?php

namespace KRG\CoreBundle\Security\Voter;

use KRG\CoreBundle\Annotation\Exclude;
use KRG\CoreBundle\Annotation\IsGranted;
use KRG\CoreBundle\Mapping\ClassAnnotationMapping;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class AccessVoter extends Voter
{
    const CREATE    = 'C';
    const READ      = 'R';
    const UPDATE    = 'U';
    const DELETE    = 'D';

    /**
     * @var FilesystemAdapter
     */
    protected $filesystemAdapter;

    public function __construct(string $dataCacheDir)
    {
        $this->filesystemAdapter = new FilesystemAdapter('annotation', 0, $dataCacheDir);
    }

    protected function supports($attribute, $subject)
    {

        return $subject !== null;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        switch($attribute) {
            case 'new':
                $attribute = 'C';
                break;
            case 'list':
            case 'search':
            case 'show':
                $attribute = 'R';
                break;
            case 'edit':
                $attribute = 'U';
                break;
            case 'delete':
                $attribute = 'D';
                break;
            default:
                break;
        }

        $action = '';
        if (in_array($attribute, ['C', 'R', 'U', 'D'])) {
            $action = $attribute;
        }

        $securityAccessList = $this->getSecurityAccessList();

        if (is_array($subject) && array_key_exists('class', $subject)) {
            $subject = $subject['class'];
        }

        if (is_object($subject)) {
            $subject = get_class($subject);
        }

        foreach ($securityAccessList['exclude'] as $className => $exclude) {
            if ($subject === $className) {
                if ($this->checkRoleAction($token, $exclude->value, $action)) {
                    return false;
                }
            }
        }

        foreach ($securityAccessList['isGranted'] as $className => $isGranted) {
            if ($subject === $className) {
                return $this->checkRoleAction($token, $isGranted->value, $action);
            }
        }

        return true;
    }

    private function checkRoleAction(TokenInterface $token, $roles, $action) {
        foreach ($roles as $role => $role_action) {
            foreach($token->getRoles() as $_role) {
                if ($_role->getRole() === $role) {
                    if ($role_action === 'CRUD') {
                        return true;
                    }
                    if (in_array($action, str_split($role_action))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function getSecurityAccessList()
    {
        $item = $this->filesystemAdapter->getItem('securityAccessList');
        if ($item->isHit()) {
            return $item->get();
        }

        $excludeAnnotation = ClassAnnotationMapping::getAnnotationForNamespace(Exclude::class, ['AppBundle', 'KRG', 'GEGM']);
        $isGrantedAnnotation = ClassAnnotationMapping::getAnnotationForNamespace(IsGranted::class, ['AppBundle', 'KRG', 'GEGM']);

        $securityAccessList = [
          'exclude' => $excludeAnnotation,
          'isGranted' => $isGrantedAnnotation,
        ];

        $item->set($securityAccessList);
        $this->filesystemAdapter->save($item);

        return $securityAccessList;

    }

}