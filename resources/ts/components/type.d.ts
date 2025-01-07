export type Attachment = {
  id: string;
  post_id: string;
  file_name: string;
  file_path: string;
  file_type: string;
};


export type Post = {
  id: string;
  user_id: string;
  title: string;
  content: string;
  status: 'D' | 'P';
  visibility: 'P' | 'C';
  comment_control: 'A' | 'C' | 'N';
  likeCount: number;
  commentCount:number
  attachments: Attachment[];
  hasLike:boolean
  hasCommentAllowed:boolean
};

export type PostSetting = {
  whoCanSeePost: 'P' | 'C';
  commentControl: 'A' | 'C' | 'N';
};
