'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

interface LoadingLinkProps {
  href: string;
  children: React.ReactNode;
  className?: string;
  onClick?: () => void;
}

export default function LoadingLink({ href, children, className, onClick }: LoadingLinkProps) {
  const router = useRouter();
  const [isLoading, setIsLoading] = useState(false);

  const handleClick = (e: React.MouseEvent<HTMLAnchorElement>) => {
    if (onClick) {
      onClick();
    }
    
    // Only show loading for internal links
    if (href.startsWith('/')) {
      setIsLoading(true);
      router.push(href);
    }
  };

  return (
    <Link href={href} className={className} onClick={handleClick}>
      {isLoading ? (
        <span className="flex items-center gap-2">
          <span className="material-symbols-outlined animate-spin text-sm">refresh</span>
          {children}
        </span>
      ) : (
        children
      )}
    </Link>
  );
}
